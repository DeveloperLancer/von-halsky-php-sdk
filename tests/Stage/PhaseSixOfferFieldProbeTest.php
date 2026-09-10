<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferStatus;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductPatch;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixOfferFieldProbeTest extends StageTestCase
{
    public function testLocalProductFieldBoundariesAreRejectedBeforeNetworkIo(): void
    {
        $this->expectLocalRejection(static fn () => new ProductProposal(
            str_repeat('N', 6),
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        ));
        $this->expectLocalRejection(static fn () => new ProductProposal(
            str_repeat('N', 151),
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        ));
        $this->expectLocalRejection(static fn () => new ProductProposal(
            'Product',
            str_repeat('D', 100),
            str_repeat('B', 101),
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        ));
        $this->expectLocalRejection(static fn () => new ProductProposal(
            'Product',
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
            model: str_repeat('M', 101),
        ));
        $this->expectLocalRejection(static fn () => new ProductProposal(
            'Product',
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
            superModel: str_repeat('S', 101),
        ));
        $this->expectLocalRejection(static fn () => new Ean('ABC'));
        $this->expectLocalRejection(static fn () => new Ean(str_repeat('1', 15)));
        $this->expectLocalRejection(static fn () => new Price(Money::fromDecimal('9.99'), ''));
        $this->expectLocalRejection(static fn () => new ProductPatch(name: OptionalValue::of('')));
    }

    public function testProductFieldPatchesAndIdentityAssignmentOnStage(): void
    {
        $offers = $this->stageOrganization()->offers();
        $created = null;
        $failure = null;

        try {
            $created = $this->createSyntheticOffer(['externalId' => null]);
            $originalName = self::productField($created->offer, 'name');
            $originalBrand = self::productField($created->offer, 'brand');

            $this->assertProductPatchOutcome($offers, $created->offer->status->value, $created, 'Produkt');
            $this->patchProduct($offers, $created, str_repeat('N', 150), $originalBrand);
            $this->waitForOfferMatching(
                $offers,
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::productField($offer, 'name') === str_repeat('N', 150),
                'The 150-character product name was not visible after PATCH.',
            );
            $this->patchProduct($offers, $created, $originalName, 'A');
            $this->waitForOfferMatching(
                $offers,
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::productField($offer, 'brand') === 'A',
                'The 1-character brand was not visible after PATCH.',
            );
            $this->patchProduct($offers, $created, $originalName, str_repeat('B', 100), str_repeat('M', 100), str_repeat('S', 100));
            $this->waitForOfferMatching(
                $offers,
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::productField($offer, 'brand') === str_repeat('B', 100)
                    && self::optionalProductField($offer, 'model') === str_repeat('M', 100)
                    && self::optionalProductField($offer, 'superModel') === str_repeat('S', 100),
                'The 100-character brand, model, and superModel were not visible after PATCH.',
            );
            $this->patchProduct($offers, $created, $originalName, $originalBrand);

            $firstExternalId = $this->uniqueExternalId('-ext');
            $offers->patch($created->offerId, new PatchOfferRequest(externalId: OptionalValue::of($firstExternalId)));
            $this->waitForOfferMatching(
                $offers,
                $created->offerId,
                static fn (OfferDetails $offer): bool => self::offerExternalId($offer) === $firstExternalId,
                'The first external ID assignment was not visible after PATCH.',
            );
            $repeated = $this->uniqueExternalId('-ext2');
            try {
                $offers->patch($created->offerId, new PatchOfferRequest(externalId: OptionalValue::of($repeated)));
                $afterRepeat = $offers->get($created->offerId)->data;
                self::assertContains(self::offerExternalId($afterRepeat), [$firstExternalId, $repeated]);
            } catch (ApiException) {
                self::addToAssertionCount(1);
            }

            $currentEan = self::optionalProductField($created->offer, 'ean') ?? $this->stageConfig()->productEan;
            $offers->patch($created->offerId, new PatchOfferRequest(
                product: OptionalValue::of(new ProductPatch(ean: OptionalValue::of(new Ean($currentEan)))),
            ));
            self::addToAssertionCount(1);
            try {
                $offers->patch($created->offerId, new PatchOfferRequest(
                    product: OptionalValue::of(new ProductPatch(ean: OptionalValue::of(new Ean('0000000000000')))),
                ));
                self::addToAssertionCount(1);
            } catch (ApiException) {
                self::addToAssertionCount(1);
            }

            try {
                $offers->patch($created->offerId, new PatchOfferRequest(
                    price: OptionalValue::of(new Price(Money::fromDecimal('9.99'), 'NOT_A_TAX_RATE')),
                ));
                self::addToAssertionCount(1);
            } catch (ApiException) {
                self::addToAssertionCount(1);
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

    private function assertProductPatchOutcome(
        OffersResource $offers,
        string $statusWhenFirstSeen,
        StageCreatedOffer $created,
        string $name,
    ): void {
        $this->patchProduct($offers, $created, $name, self::productField($created->offer, 'brand'));
        $this->waitForOfferMatching(
            $offers,
            $created->offerId,
            static fn (OfferDetails $offer): bool => self::productField($offer, 'name') === $name,
            sprintf('A product PATCH was not visible while the offer status was %s.', $statusWhenFirstSeen),
        );
        if ($statusWhenFirstSeen !== OfferStatus::PUBLISHED) {
            try {
                $this->waitForOfferStatus($offers, $created->offerId, OfferStatus::PUBLISHED);
                $this->patchProduct($offers, $created, str_repeat('P', 7), self::productField($created->offer, 'brand'));
                $this->waitForOfferMatching(
                    $offers,
                    $created->offerId,
                    static fn (OfferDetails $offer): bool => self::productField($offer, 'name') === str_repeat('P', 7),
                    'A product PATCH was not visible after the offer reached PUBLISHED.',
                );
            } catch (InvalidRequestException | ApiException) {
                self::addToAssertionCount(1);
            }
        }
    }

    private function patchProduct(
        OffersResource $offers,
        StageCreatedOffer $created,
        string $name,
        string $brand,
        ?string $model = null,
        ?string $superModel = null,
    ): void {
        $offers->patch($created->offerId, new PatchOfferRequest(
            product: OptionalValue::of(new ProductPatch(
                name: OptionalValue::of($name),
                brand: OptionalValue::of($brand),
                model: $model === null ? OptionalValue::undefined() : OptionalValue::of($model),
                superModel: $superModel === null ? OptionalValue::undefined() : OptionalValue::of($superModel),
            )),
        ));
    }

    private static function productField(OfferDetails $offer, string $field): string
    {
        $value = $offer->product[$field] ?? null;
        if (!is_string($value) || $value === '') {
            self::fail(sprintf('The Stage offer product did not contain a non-empty %s.', $field));
        }

        return $value;
    }

    private static function optionalProductField(OfferDetails $offer, string $field): ?string
    {
        $value = $offer->product[$field] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function offerExternalId(OfferDetails $offer): ?string
    {
        $value = $offer->additionalData()['externalId'] ?? $offer->metadata['externalId'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
