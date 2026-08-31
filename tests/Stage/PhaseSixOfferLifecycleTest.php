<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\CommandDetails;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStatus;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\Request\OfferListOptions;
use DevLancer\VonHalsky\Request\ProductHintOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixOfferLifecycleTest extends StageTestCase
{
    public function testCreateBrowseUpdateAndCloseOfferOnStage(): void
    {
        $configuration = $this->stageConfig();
        $offers = $this->stageOrganization()->offers();
        $offerId = null;
        $failure = null;

        try {
            $externalId = 'sdk-stage-' . bin2hex(random_bytes(12));
            $product = $this->stageProductHint($offers, $configuration->productEan);
            $create = $offers->create(new CreateOfferRequest(
                new ProductProposal(
                    self::productString($product, 'name'),
                    self::stageDescription($product),
                    self::productString($product, 'brand'),
                    $this->stageLeafCategoryId(),
                    new Ean($configuration->productEan),
                    attributes: $this->requiredCategoryAttributes(),
                ),
                new Stock(1),
                new Price(Money::fromDecimal('9.99'), '23%'),
                GpsrInfo::notRequired(),
                $externalId,
                1,
                [new OfferImage('sdk-stage-offer.png', $configuration->offerImageUrl, 1)],
            ));
            $offerId = $create->data->offerId;
            self::assertContains($create->statusCode, [200, 201]);
            self::assertNotNull($offerId, 'The Stage API accepted the create command without returning an offer ID.');
            $this->waitForSuccessfulCommand($offers, $create->data->commandId);

            $created = $this->waitForOffer($offers, $offerId);
            self::assertSame($offerId->value, $created->id->value);
            self::assertNotEmpty($created->additionalData()['images'] ?? null, 'The created Stage offer does not contain an image.');
            $this->assertOfferAppearsInList($offers, $offerId);

            $priceHandles = $offers->updatePrices([
                new OfferPriceUpdate($offerId, Money::fromDecimal('10.99')),
            ])->data;
            self::assertCount(1, $priceHandles);
            $this->waitForSuccessfulCommand($offers, $priceHandles[0]->commandId);

            $stockHandles = $offers->updateStocks([
                new OfferStockUpdate($offerId, new Stock(2)),
            ])->data;
            self::assertCount(1, $stockHandles);
            $this->waitForSuccessfulCommand($offers, $stockHandles[0]->commandId);

            $updated = $this->waitForOfferUpdates($offers, $offerId, '10.99', 2);
            self::assertSame('10.99', self::priceAmount($updated->price));
            self::assertSame(2, self::integerValue($updated->stock['quantity'] ?? null, 'offer stock'));
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            if ($offerId instanceof OfferId) {
                try {
                    $close = $offers->close($offerId);
                    $this->waitForSuccessfulCommand($offers, $close->data->commandId);
                    $this->waitForOfferStatus($offers, $offerId, OfferStatus::CLOSED);
                } catch (Throwable $cleanupFailure) {
                    $failure ??= $cleanupFailure;
                }
            }
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    private function waitForSuccessfulCommand(OffersResource $offers, CommandId $commandId): CommandDetails
    {
        $configuration = $this->stageConfig();
        $deadline = microtime(true) + $configuration->commandTimeoutSeconds;

        do {
            $command = $offers->command($commandId)->data;
            if ($command->status->value === 'SUCCESS') {
                if ($command->errors !== []) {
                    self::fail('A Stage offer command completed with validation errors.');
                }

                return $command;
            }
            if ($command->status->value === 'FAILURE') {
                self::fail('A Stage offer command finished with FAILURE.');
            }
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail('A Stage offer command did not finish before the configured timeout.');
    }

    private function waitForOfferStatus(OffersResource $offers, OfferId $offerId, string $expectedStatus): void
    {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            if ($offers->get($offerId)->data->status->value === $expectedStatus) {
                self::addToAssertionCount(1);

                return;
            }
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail('The Stage offer did not reach the expected lifecycle status before the configured timeout.');
    }

    private function waitForOffer(OffersResource $offers, OfferId $offerId): OfferDetails
    {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            try {
                return $offers->get($offerId)->data;
            } catch (NotFoundException) {
                foreach ($offers->events(new OfferEventsOptions(limit: 100))->data as $event) {
                    if ($event->offerId->value === $offerId->value
                        && in_array($event->type->value, ['VALIDATION_FAILED', 'REJECTED'], true)
                    ) {
                        self::fail(sprintf(
                            'Stage asynchronously rejected the offer with %s. The offer event feed contains no field-level validation details.',
                            $event->type->value,
                        ));
                    }
                }
                $this->waitBeforeNextPoll($deadline);
            }
        } while (microtime(true) < $deadline);

        self::fail('The Stage offer was not readable before the configured timeout.');
    }

    private function assertOfferAppearsInList(OffersResource $offers, OfferId $offerId): void
    {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            $offset = 0;
            do {
                $page = $offers->list(new OfferListOptions(limit: 30, offset: $offset, sort: ['-createdAt']))->data;
                foreach ($page->items as $offer) {
                    if ($offer->id->value === $offerId->value) {
                        self::addToAssertionCount(1);

                        return;
                    }
                }
                $offset = $page->page->offset + $page->page->limit;
            } while ($offset < $page->page->total);
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail('The newly created Stage offer was not present in the paginated offer list.');
    }

    private function waitForOfferUpdates(
        OffersResource $offers,
        OfferId $offerId,
        string $expectedPrice,
        int $expectedStock,
    ): OfferDetails {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            $offer = $offers->get($offerId)->data;
            if (self::priceAmount($offer->price) === $expectedPrice
                && self::integerValue($offer->stock['quantity'] ?? null, 'offer stock') === $expectedStock
            ) {
                return $offer;
            }
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail('The Stage offer updates were not visible before the configured timeout.');
    }

    private function waitBeforeNextPoll(float $deadline): void
    {
        $remainingMilliseconds = (int) max(0, ($deadline - microtime(true)) * 1000);
        $sleepMilliseconds = min($this->stageConfig()->pollIntervalMilliseconds, $remainingMilliseconds);
        if ($sleepMilliseconds > 0) {
            usleep($sleepMilliseconds * 1000);
        }
    }

    /** @param array<string, mixed> $price */
    private static function priceAmount(array $price): string
    {
        $grossPrice = $price['grossPrice'] ?? null;
        if (!is_array($grossPrice)) {
            self::fail('The Stage offer price did not contain a gross price object.');
        }

        return self::decimal($grossPrice['amount'] ?? null, 'offer price');
    }

    private static function decimal(mixed $value, string $label): string
    {
        if ((!is_int($value) && !is_float($value) && !is_string($value)) || !is_numeric($value)) {
            self::fail(sprintf('The Stage %s was not numeric.', $label));
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function integerValue(mixed $value, string $label): int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^-?[0-9]+$/D', $value) === 1)) {
            self::fail(sprintf('The Stage %s was not an integer.', $label));
        }

        return (int) $value;
    }

    /** @return array<string, mixed> */
    private function stageProductHint(OffersResource $offers, string $ean): array
    {
        $hints = $offers->hints(new ProductHintOptions(
            ean: new Ean($ean),
            limit: 1,
            language: ResponseLanguage::POLISH,
        ))->data;
        if ($hints->items === []) {
            self::fail('The configured Stage product_ean has no product hint. Use a Stage catalogue EAN.');
        }
        $product = $hints->items[0]->product;
        $categoryId = $product['categoryId'] ?? null;
        if (!is_string($categoryId) || $categoryId !== $this->stageLeafCategoryId()->value) {
            self::fail('The configured Stage product hint belongs to a different category than leaf_category_id.');
        }

        return $product;
    }

    /** @param array<string, mixed> $product */
    private static function productString(array $product, string $field): string
    {
        $value = $product[$field] ?? null;
        if (!is_string($value) || $value === '') {
            self::fail(sprintf('The Stage product hint did not contain a non-empty %s.', $field));
        }

        return $value;
    }

    /** @param array<string, mixed> $product */
    private static function stageDescription(array $product): string
    {
        $description = trim(self::productString($product, 'description'));
        $suffix = ' Oferta testowa integracji Von Halsky SDK uruchamiana wyłącznie w środowisku Stage.';
        if (mb_strlen($description . $suffix) < 100) {
            $suffix .= ' Nie jest przeznaczona do rzeczywistej sprzedaży ani realizacji zamówień.';
        }

        $result = $description . $suffix;
        self::assertGreaterThanOrEqual(100, mb_strlen($result));

        return $result;
    }

    /** @return list<AttributeValue> */
    private function requiredCategoryAttributes(): array
    {
        $valuesByName = [
            'Bohater / Bajka' => 'serduszka',
            'Kolor' => 'wielokolorowy',
            'Płeć' => 'dziewczynka',
            'Typ' => 'miejski',
            'Wielkość' => 'duża (mieszcząca A4)',
        ];
        $definitions = $this->stageClient()->categories()->attributes(
            $this->stageLeafCategoryId(),
            ResponseLanguage::POLISH,
        )->data;

        $result = [];
        foreach ($definitions as $definition) {
            if (!in_array($definition->expectedValue->value, ['ONE', 'AT_LEAST_ONE'], true)) {
                continue;
            }
            if ($definition->type->value !== 'TEXT_VALUE' || $definition->dictionary !== null) {
                self::fail(sprintf('Required Stage category attribute "%s" needs an unsupported fixture value type.', $definition->name));
            }
            if (!isset($valuesByName[$definition->name])) {
                self::fail(sprintf('No fixture value is configured for required Stage category attribute "%s".', $definition->name));
            }
            $result[] = new AttributeValue(
                $definition->id,
                [$valuesByName[$definition->name]],
                ResponseLanguage::POLISH->value,
            );
        }

        self::assertCount(5, $result, 'The Stage Plecaki szkolne category no longer has the expected required attributes.');

        return $result;
    }
}
