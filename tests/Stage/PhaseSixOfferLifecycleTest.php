<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Model\Offer\CommandDetails;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStatus;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Request\OfferListOptions;
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
            $create = $offers->create(new CreateOfferRequest(
                new ProductProposal(
                    'SDK Stage verification',
                    'Temporary synthetic offer created by the SDK Stage test suite.',
                    'SDK Test',
                    $this->stageLeafCategoryId(),
                    new Ean($configuration->productEan),
                ),
                new Stock(1),
                new Price(Money::fromDecimal('9.99'), '23%'),
                GpsrInfo::notRequired(),
                $externalId,
                1,
            ));
            self::assertSame(201, $create->statusCode);
            $offerId = $create->data->offerId;
            self::assertNotNull($offerId, 'The Stage API accepted the create command without returning an offer ID.');
            $this->waitForSuccessfulCommand($offers, $create->data->commandId);

            $created = $offers->get($offerId);
            self::assertSame(200, $created->statusCode);
            self::assertSame($offerId->value, $created->data->id->value);
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

            $updated = $offers->get($offerId)->data;
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

    private function assertOfferAppearsInList(OffersResource $offers, OfferId $offerId): void
    {
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

        self::fail('The newly created Stage offer was not present in the paginated offer list.');
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
}
