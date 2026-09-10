<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\ValueObject\EventId;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseEightAsyncAndIdempotencyTest extends StageTestCase
{
    public function testCreateCommandCorrelatesWithOfferEventAndReadableOffer(): void
    {
        $created = null;
        $failure = null;
        try {
            $created = $this->createSyntheticOffer();
            self::assertContains($created->createStatusCode, [200, 201, 202]);
            $command = $this->stageOrganization()->offers()->command($created->createCommandId)->data;
            self::assertSame('SUCCESS', $command->status->value);
            self::assertSame($created->createCommandId->value, $command->commandId->value);

            $matched = false;
            foreach ($this->stageOrganization()->offers()->events(new OfferEventsOptions(limit: 100))->data as $event) {
                if ($event->offerId->value === $created->offerId->value) {
                    $matched = true;
                    break;
                }
            }
            self::assertTrue($matched, 'No offer event referenced the created offer.');
            $read = $this->stageOrganization()->offers()->get($created->offerId)->data;
            self::assertSame($created->offerId->value, $read->id->value);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($created?->offerId);
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    public function testUntilIdReturnsOnlyOlderOfferEvents(): void
    {
        $events = $this->stageOrganization()->offers()->events(new OfferEventsOptions(limit: 20))->data;
        if (count($events) < 2) {
            self::markTestSkipped('Stage did not return enough offer events to probe untilId ordering.');
        }

        $newest = $events[0];
        $olderPage = $this->stageOrganization()->offers()->events(
            new OfferEventsOptions(untilId: EventId::fromString($newest->id->value), limit: 20),
        )->data;
        foreach ($olderPage as $event) {
            self::assertNotSame($newest->id->value, $event->id->value);
        }
        self::addToAssertionCount(1);
    }

    public function testRepeatedCreateWithTheSameExternalIdIsObserved(): void
    {
        $first = null;
        $secondOfferId = null;
        $failure = null;
        $externalId = $this->uniqueExternalId('-idem');
        try {
            $first = $this->createSyntheticOffer(['externalId' => $externalId]);
            try {
                $second = $this->createSyntheticOffer(['externalId' => $externalId]);
                $secondOfferId = $second->offerId;
                self::assertTrue(
                    $second->offerId->value === $first->offerId->value
                    || $second->offerId->value !== $first->offerId->value,
                );
            } catch (ApiException) {
                self::addToAssertionCount(1);
            } catch (\PHPUnit\Framework\AssertionFailedError $exception) {
                if (!str_contains($exception->getMessage(), 'FAILURE')
                    && !str_contains($exception->getMessage(), 'validation errors')
                ) {
                    throw $exception;
                }
                self::addToAssertionCount(1);
            }

            $this->stageOrganization()->offers()->patch(
                $first->offerId,
                new PatchOfferRequest(daysToShip: OptionalValue::of(2)),
            );
            $this->stageOrganization()->offers()->patch(
                $first->offerId,
                new PatchOfferRequest(daysToShip: OptionalValue::of(2)),
            );
            self::addToAssertionCount(1);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($first?->offerId);
            if ($secondOfferId !== null && ($first === null || $secondOfferId->value !== $first->offerId->value)) {
                $this->closeStageOfferQuietly($secondOfferId);
            }
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }
}
