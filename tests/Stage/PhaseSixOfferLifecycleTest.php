<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Request\OfferListOptions;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixOfferLifecycleTest extends StageTestCase
{
    public function testCreateBrowseUpdateAndCloseOfferOnStage(): void
    {
        $offers = $this->stageOrganization()->offers();
        $created = null;
        $failure = null;

        try {
            $created = $this->createSyntheticOffer();
            self::assertSame($created->offerId->value, $created->offer->id->value);
            self::assertNotEmpty($created->offer->additionalData()['images'] ?? null, 'The created Stage offer does not contain an image.');
            $this->assertOfferAppearsInList($offers, $created->offerId);

            $priceHandles = $offers->updatePrices([
                new OfferPriceUpdate($created->offerId, Money::fromDecimal('10.99')),
            ])->data;
            self::assertCount(1, $priceHandles);
            $this->waitForSuccessfulCommand($offers, $priceHandles[0]->commandId);

            $stockHandles = $offers->updateStocks([
                new OfferStockUpdate($created->offerId, new Stock(2)),
            ])->data;
            self::assertCount(1, $stockHandles);
            $this->waitForSuccessfulCommand($offers, $stockHandles[0]->commandId);

            $updated = $this->waitForOfferMatching(
                $offers,
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::priceAmount($offer->price) === '10.99'
                    && self::integerValue($offer->stock['quantity'] ?? null, 'offer stock') === 2,
                'The Stage offer updates were not visible before the configured timeout.',
            );
            self::assertSame('10.99', self::priceAmount($updated->price));
            self::assertSame(2, self::integerValue($updated->stock['quantity'] ?? null, 'offer stock'));
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            if ($created instanceof StageCreatedOffer) {
                try {
                    $this->closeStageOffer($created->offerId);
                } catch (Throwable $cleanupFailure) {
                    $failure ??= $cleanupFailure;
                }
            }
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
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
}
