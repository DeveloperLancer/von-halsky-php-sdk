<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\Manufacturer;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\ResponsiblePerson;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CountryCode;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixGpsrProbeTest extends StageTestCase
{
    public function testStructuredGpsrFieldsPersistOnStage(): void
    {
        $created = null;
        $failure = null;

        try {
            $created = $this->createSyntheticOffer(['gpsr' => self::gpsr(true)]);
            $this->waitForOfferMatching(
                $this->stageOrganization()->offers(),
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::gpsrSection($offer) !== null,
                'Structured GPSR data was not visible on the created Stage offer.',
            );

            $this->stageOrganization()->offers()->patch(
                $created->offerId,
                new PatchOfferRequest(gpsr: OptionalValue::of(self::gpsr(false))),
            );
            $this->waitForOfferMatching(
                $this->stageOrganization()->offers(),
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::ceMarking($offer) === false,
                'GPSR ceMarking=false was not visible after PATCH.',
            );

            $this->stageOrganization()->offers()->patch(
                $created->offerId,
                new PatchOfferRequest(gpsr: OptionalValue::of(self::gpsr(true))),
            );
            $this->waitForOfferMatching(
                $this->stageOrganization()->offers(),
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::ceMarking($offer) === true,
                'Restored GPSR ceMarking=true was not visible after PATCH.',
            );
        } catch (ApiException $exception) {
            $this->assertProblemJson($exception);
            self::addToAssertionCount(1);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($created?->offerId);
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    private static function gpsr(bool $ceMarking): GpsrInfo
    {
        $address = new Address('Testowa', 'Warszawa', '00-001', new CountryCode('PL'), '1');

        return GpsrInfo::required(
            new Manufacturer(
                'Stage SDK Test Manufacturer',
                'stage-sdk-test@example.com',
                '+48123456789',
                new CountryCode('PL'),
                $address,
                null,
                new ResponsiblePerson(
                    'Stage Responsible Person',
                    'stage-responsible@example.com',
                    '+48123456780',
                    new CountryCode('PL'),
                    $address,
                ),
            ),
            'Safety information for Stage SDK verification of structured GPSR fields and CE marking.',
            [],
            'BATCH-SDK-1',
            $ceMarking,
        );
    }

    /** @return array<string, mixed>|null */
    private static function gpsrSection(OfferDetails $offer): ?array
    {
        foreach ([$offer->additionalData(), $offer->product, $offer->metadata] as $source) {
            foreach (['gpsr', 'gpsrInfo'] as $key) {
                $value = $source[$key] ?? null;
                if (is_array($value) && $value !== []) {
                    /** @var array<string, mixed> $value */
                    return $value;
                }
            }
        }

        return null;
    }

    private static function ceMarking(OfferDetails $offer): ?bool
    {
        $gpsr = self::gpsrSection($offer);
        if ($gpsr === null) {
            return null;
        }
        $value = $gpsr['ceMarking'] ?? null;

        return is_bool($value) ? $value : null;
    }
}
