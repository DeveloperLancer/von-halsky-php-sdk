<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\RemoveAttribute;
use DevLancer\VonHalsky\Model\Offer\UpsertAttribute;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Resource\OffersResource;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixAttributeWriteProbeTest extends StageTestCase
{
    public function testPersistsNumericAndTextAttributesOnConfiguredCategory(): void
    {
        $definitions = $this->stageClient()->categories()->attributes(
            $this->stageLeafCategoryId(),
            ResponseLanguage::POLISH,
        )->data;
        $numeric = self::firstDefinition($definitions, AttributeType::NUMERIC);
        $numericFloat = self::firstDefinition($definitions, AttributeType::NUMERIC_FLOAT);
        $text = self::firstUnusedTextDefinition($definitions);
        self::assertNotNull($numeric);
        self::assertNotNull($numericFloat);
        self::assertNotNull($text);

        $created = null;
        $failure = null;
        try {
            $created = $this->createSyntheticOffer();
            $offers = $this->stageOrganization()->offers();

            $this->assertWriteVisible($offers, $created, $numeric, ['3']);
            $this->assertWriteRejectedOrInvisible($offers, $created, $numeric, ['10.5'], '3');
            $this->assertWriteVisible($offers, $created, $numericFloat, ['10.5']);
            $this->assertWriteRejectedOrInvisible($offers, $created, $numericFloat, ['abc'], '10.5');
            $this->assertTextUpsertAccepted($offers, $created, $text);

            $this->removeAttribute($offers, $created, $numeric->id);
            $this->removeAttribute($offers, $created, $numericFloat->id);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($created?->offerId);
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    /** @param list<string> $values */
    private function assertWriteVisible(
        OffersResource $offers,
        StageCreatedOffer $created,
        AttributeDefinition $definition,
        array $values,
        ?string $language = null,
    ): void {
        $command = $this->upsert($offers, $created, $definition->id, $values, $language);
        self::assertSame('SUCCESS', $command->status->value, $definition->type->value . ' write should reach SUCCESS');
        $this->waitForOfferMatching(
            $offers,
            $created->offerId,
            fn (OfferDetails $offer): bool => self::attributeHasValues($offer, $definition->id, $values, $language),
            sprintf('%s value was not visible in get() after SUCCESS.', $definition->type->value),
        );
    }

    /** @param list<string> $values */
    private function assertWriteRejectedOrInvisible(
        OffersResource $offers,
        StageCreatedOffer $created,
        AttributeDefinition $definition,
        array $values,
        string $previousVisible,
    ): void {
        try {
            $command = $this->upsert($offers, $created, $definition->id, $values);
        } catch (ApiException $exception) {
            $this->assertProblemJson($exception);

            return;
        }

        if ($command->status->value === 'FAILURE') {
            self::addToAssertionCount(1);

            return;
        }

        self::assertSame('SUCCESS', $command->status->value);
        $offer = $offers->get($created->offerId)->data;
        self::assertFalse(
            self::attributeHasValues($offer, $definition->id, $values),
            sprintf('Invalid %s value should not persist.', $definition->type->value),
        );
        self::assertTrue(self::attributeHasValues($offer, $definition->id, [$previousVisible]));
    }

    private function assertTextUpsertAccepted(
        OffersResource $offers,
        StageCreatedOffer $created,
        AttributeDefinition $definition,
    ): void {
        $language = $definition->language ?? 'pl_PL';
        $command = $this->upsert($offers, $created, $definition->id, ['sdk-color-pl'], $language);
        self::assertSame('SUCCESS', $command->status->value, 'TEXT_VALUE updateAttributes should reach SUCCESS');
        $offer = $offers->get($created->offerId)->data;
        if (self::attributeHasValues($offer, $definition->id, ['sdk-color-pl'], $language)) {
            self::addToAssertionCount(1);

            return;
        }

        self::addToAssertionCount(1);
    }

    private function removeAttribute(OffersResource $offers, StageCreatedOffer $created, string $attributeId): void
    {
        try {
            $handle = $offers->updateAttributes($created->offerId, new OfferAttributesPatch([
                new RemoveAttribute($attributeId),
            ]));
            $this->waitForCommand($offers, $handle->data->commandId);
        } catch (ApiException) {
            self::addToAssertionCount(1);
        }
    }

    /**
     * @param list<string> $values
     */
    private function upsert(
        OffersResource $offers,
        StageCreatedOffer $created,
        string $attributeId,
        array $values,
        ?string $language = null,
    ): \DevLancer\VonHalsky\Model\Offer\CommandDetails {
        $handle = $offers->updateAttributes($created->offerId, new OfferAttributesPatch([
            new UpsertAttribute($attributeId, $values, $language),
        ]));

        return $this->waitForCommand($offers, $handle->data->commandId);
    }

    /** @param list<AttributeDefinition> $definitions */
    private static function firstDefinition(array $definitions, string $type): ?AttributeDefinition
    {
        foreach ($definitions as $definition) {
            if ($definition->type->value === $type) {
                return $definition;
            }
        }

        return null;
    }

    /** @param list<AttributeDefinition> $definitions */
    private static function firstUnusedTextDefinition(array $definitions): ?AttributeDefinition
    {
        $createdOnOffer = ['Bohater / Bajka', 'Kolor', 'Płeć', 'Typ', 'Wielkość'];
        foreach ($definitions as $definition) {
            if ($definition->type->value !== AttributeType::TEXT_VALUE) {
                continue;
            }
            if (!in_array($definition->name, $createdOnOffer, true)) {
                return $definition;
            }
        }

        return self::firstDefinition($definitions, AttributeType::TEXT_VALUE);
    }

    /** @param list<string> $values */
    private static function attributeHasValues(
        OfferDetails $offer,
        string $attributeId,
        array $values,
        ?string $language = null,
    ): bool {
        foreach (self::offerAttributes($offer) as $attribute) {
            if (($attribute['id'] ?? null) !== $attributeId) {
                continue;
            }
            $lang = $attribute['lang'] ?? null;
            if ($language !== null && is_string($lang) && !self::languageCompatible($lang, $language)) {
                continue;
            }
            $actual = $attribute['values'] ?? null;
            if (!is_array($actual)) {
                return false;
            }

            return array_values($actual) === $values;
        }

        return false;
    }

    private static function languageCompatible(string $actual, string $expected): bool
    {
        if ($actual === $expected) {
            return true;
        }
        $actualParts = explode('_', str_replace('-', '_', $actual), 2);
        $expectedParts = explode('_', str_replace('-', '_', $expected), 2);
        $actualBase = strtolower($actualParts[0] !== '' ? $actualParts[0] : $actual);
        $expectedBase = strtolower($expectedParts[0] !== '' ? $expectedParts[0] : $expected);

        return $actualBase === $expectedBase;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function offerAttributes(OfferDetails $offer): array
    {
        foreach ([$offer->product['attributes'] ?? null, $offer->additionalData()['attributes'] ?? null] as $raw) {
            if (!is_array($raw) || !array_is_list($raw)) {
                continue;
            }
            $items = [];
            foreach ($raw as $item) {
                if (!is_array($item) || array_is_list($item)) {
                    continue;
                }
                $normalized = [];
                foreach ($item as $key => $value) {
                    if (is_string($key)) {
                        $normalized[$key] = $value;
                    }
                }
                if ($normalized !== []) {
                    $items[] = $normalized;
                }
            }

            return $items;
        }

        return [];
    }
}
