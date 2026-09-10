<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeDictionaryOption;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\UpsertAttribute;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixAttributeTypeProbeTest extends StageTestCase
{
    private const INTERESTING_TYPES = [
        AttributeType::LONG_TEXT_VALUE,
        AttributeType::DICTIONARY,
        AttributeType::DATE,
        AttributeType::URL,
        AttributeType::NUMERIC,
        AttributeType::NUMERIC_FLOAT,
    ];

    public function testScansCategoryAttributeTypesAndProbesWritableOnes(): void
    {
        $definitions = $this->stageClient()->categories()->attributes(
            $this->stageLeafCategoryId(),
            ResponseLanguage::POLISH,
        )->data;
        $typesOnConfiguredCategory = self::typeSet($definitions);
        self::assertArrayHasKey(AttributeType::TEXT_VALUE, $typesOnConfiguredCategory);

        $seen = $this->discoverTypesBeyondConfiguredCategory($typesOnConfiguredCategory);
        self::assertArrayHasKey(AttributeType::TEXT_VALUE, $seen);

        $probeTypes = array_values(array_intersect(self::INTERESTING_TYPES, array_keys($typesOnConfiguredCategory)));
        if ($probeTypes === []) {
            self::addToAssertionCount(1);

            return;
        }

        $created = null;
        $failure = null;
        try {
            $created = $this->createSyntheticOffer();
            foreach ($definitions as $definition) {
                if (!in_array($definition->type->value, $probeTypes, true)) {
                    continue;
                }
                $this->probeAttribute($created, $definition);
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($created?->offerId);
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    /**
     * @param array<string, true> $alreadySeen
     * @return array<string, true>
     */
    private function discoverTypesBeyondConfiguredCategory(array $alreadySeen): array
    {
        $tree = $this->stageClient()->categories()->list(new CategoryTreeOptions(depth: 4, language: ResponseLanguage::POLISH))->data;
        $seen = $alreadySeen;
        $fetches = 0;
        foreach (self::collectLeaves($tree) as $leaf) {
            if ($leaf->id->value === $this->stageLeafCategoryId()->value) {
                continue;
            }
            if ($fetches >= 25 || self::hasAllInterestingTypes($seen)) {
                break;
            }
            $definitions = $this->stageClient()->categories()->attributes($leaf->id, ResponseLanguage::POLISH)->data;
            ++$fetches;
            $seen += self::typeSet($definitions);
        }

        return $seen;
    }

    private function probeAttribute(StageCreatedOffer $created, AttributeDefinition $definition): void
    {
        $offers = $this->stageOrganization()->offers();
        if ($definition->type->value === AttributeType::DICTIONARY) {
            $this->probeDictionary($created, $definition);

            return;
        }

        $sample = match ($definition->type->value) {
            AttributeType::LONG_TEXT_VALUE => str_repeat('L', 200),
            AttributeType::DATE => '2026-01-15',
            AttributeType::URL => 'https://example.com/stage-sdk',
            AttributeType::NUMERIC => '10',
            AttributeType::NUMERIC_FLOAT => '10.5',
            default => 'probe',
        };

        try {
            $handle = $offers->updateAttributes($created->offerId, new OfferAttributesPatch([
                new UpsertAttribute($definition->id, [$sample], ResponseLanguage::POLISH->value),
            ]));
            $command = $this->waitForCommand($offers, $handle->data->commandId);
            self::assertContains($command->status->value, ['SUCCESS', 'FAILURE']);
        } catch (ApiException) {
            self::addToAssertionCount(1);
        }
    }

    private function probeDictionary(StageCreatedOffer $created, AttributeDefinition $definition): void
    {
        $dictionary = $definition->dictionary;
        if ($dictionary === null || $dictionary->options === []) {
            self::addToAssertionCount(1);

            return;
        }

        $active = self::firstOption($dictionary->options, true);
        $inactive = self::firstOption($dictionary->options, false);
        $offers = $this->stageOrganization()->offers();

        if ($active instanceof AttributeDictionaryOption) {
            $this->upsertAndWait($created, $definition->id, [$active->value]);
            $this->upsertAndWait($created, $definition->id, [$active->id]);
        }
        $this->upsertAndWait($created, $definition->id, ['sdk-absent-dictionary-value']);
        if ($inactive instanceof AttributeDictionaryOption) {
            $this->upsertAndWait($created, $definition->id, [$inactive->value]);
        }
        unset($offers);
    }

    /** @param list<string> $values */
    private function upsertAndWait(StageCreatedOffer $created, string $attributeId, array $values): void
    {
        $offers = $this->stageOrganization()->offers();
        try {
            $handle = $offers->updateAttributes($created->offerId, new OfferAttributesPatch([
                new UpsertAttribute($attributeId, $values, ResponseLanguage::POLISH->value),
            ]));
            $command = $this->waitForCommand($offers, $handle->data->commandId);
            self::assertContains($command->status->value, ['SUCCESS', 'FAILURE']);
        } catch (ApiException) {
            self::addToAssertionCount(1);
        }
    }

    /**
     * @param list<AttributeDefinition> $definitions
     * @return array<string, true>
     */
    private static function typeSet(array $definitions): array
    {
        $types = [];
        foreach ($definitions as $definition) {
            $types[$definition->type->value] = true;
        }

        return $types;
    }

    /**
     * @param array<string, true> $seen
     */
    private static function hasAllInterestingTypes(array $seen): bool
    {
        foreach (self::INTERESTING_TYPES as $type) {
            if (!isset($seen[$type])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Category> $categories
     * @return list<Category>
     */
    private static function collectLeaves(array $categories): array
    {
        $leaves = [];
        foreach ($categories as $category) {
            if ($category->leaf) {
                $leaves[] = $category;
            }
            foreach (self::collectLeaves($category->children) as $child) {
                $leaves[] = $child;
            }
        }

        return $leaves;
    }

    /**
     * @param list<AttributeDictionaryOption> $options
     */
    private static function firstOption(array $options, bool $active): ?AttributeDictionaryOption
    {
        foreach ($options as $option) {
            if ($option->active === $active && $option->value !== '') {
                return $option;
            }
        }

        return null;
    }
}
