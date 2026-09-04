<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Merge-patch payload preserving absent/null/value states. */
final class PatchOfferRequest implements RequestDtoInterface
{
    /** @var OptionalValue<string> */
    public readonly OptionalValue $externalId;
    /** @var OptionalValue<ProductPatch|null> */
    public readonly OptionalValue $product;
    /** @var OptionalValue<Price|null> */
    public readonly OptionalValue $price;
    /** @var OptionalValue<Stock|null> */
    public readonly OptionalValue $stock;
    /** @var OptionalValue<GpsrInfo|null> */
    public readonly OptionalValue $gpsr;
    /** @var OptionalValue<int> */
    public readonly OptionalValue $daysToShip;
    /** @var OptionalValue<list<OfferImage>|null> */
    public readonly OptionalValue $images;
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $affiliationProductUrl;
    /** @var OptionalValue<PostSalePatch|null> */
    public readonly OptionalValue $postSale;

    /**
     * @param OptionalValue<Price|null>|null    $price
     * @param OptionalValue<Stock|null>|null    $stock
     * @param OptionalValue<GpsrInfo|null>|null $gpsr
     * @param OptionalValue<int>|null           $daysToShip
     * @param OptionalValue<list<OfferImage>|null>|null $images
     * @param OptionalValue<string>|null        $externalId
     * @param OptionalValue<ProductPatch|null>|null $product
     * @param OptionalValue<string|null>|null   $affiliationProductUrl
     * @param OptionalValue<PostSalePatch|null>|null $postSale
     */
    public function __construct(
        ?OptionalValue $price = null,
        ?OptionalValue $stock = null,
        ?OptionalValue $gpsr = null,
        ?OptionalValue $daysToShip = null,
        ?OptionalValue $images = null,
        ?OptionalValue $externalId = null,
        ?OptionalValue $product = null,
        ?OptionalValue $affiliationProductUrl = null,
        ?OptionalValue $postSale = null,
    ) {
        $this->externalId = $externalId ?? OptionalValue::undefined();
        $this->product = $product ?? OptionalValue::undefined();
        $this->price = $price ?? OptionalValue::undefined();
        $this->stock = $stock ?? OptionalValue::undefined();
        $this->gpsr = $gpsr ?? OptionalValue::undefined();
        $this->daysToShip = $daysToShip ?? OptionalValue::undefined();
        $this->images = $images ?? OptionalValue::undefined();
        $this->affiliationProductUrl = $affiliationProductUrl ?? OptionalValue::undefined();
        $this->postSale = $postSale ?? OptionalValue::undefined();
        $this->validateExternalId();
        $this->validateInstance($this->product, ProductPatch::class, 'Offer.product');
        if ($this->daysToShip->isDefined() && !$this->daysToShip->isNull()) {
            if (!is_int($this->daysToShip->value())) {
                throw new InvalidRequestException('ShippingTime.daysToShip', 'must be an integer');
            }
            RequestValidator::daysToShip($this->daysToShip->value());
        }
        if ($this->images->isDefined() && !$this->images->isNull()) {
            $imageValues = $this->images->value();
            if (!is_array($imageValues)) {
                throw new InvalidRequestException('Offer.images', 'must be a list');
            }
            RequestValidator::offerImages($imageValues);
        }
        if ($this->affiliationProductUrl->isDefined() && !$this->affiliationProductUrl->isNull()) {
            if (!is_string($this->affiliationProductUrl->value())) {
                throw new InvalidRequestException('Offer.affiliationProductUrl', 'must be a string');
            }
            RequestValidator::stringLength($this->affiliationProductUrl->value(), 0, 2048, 'Offer.affiliationProductUrl');
        }
        $this->validateInstance($this->postSale, PostSalePatch::class, 'Offer.postSale');
    }

    public function jsonSerialize(): array
    {
        return [
            'externalId' => $this->externalId,
            'product' => $this->product,
            'price' => $this->price,
            'stock' => $this->stock,
            'gpsr' => $this->gpsr,
            'shippingTime' => !$this->daysToShip->isDefined() ? OptionalValue::undefined() : OptionalValue::of(
                $this->daysToShip->isNull() ? null : ['daysToShip' => $this->daysToShip->value()],
            ),
            'images' => $this->images,
            'affiliationProductUrl' => $this->affiliationProductUrl,
            'postSale' => $this->postSale,
        ];
    }

    private function validateExternalId(): void
    {
        if (!$this->externalId->isDefined()) {
            return;
        }
        if ($this->externalId->isNull()) {
            throw new InvalidRequestException('Offer.externalId', 'cannot be cleared after it has been assigned');
        }
        if (!is_string($this->externalId->value())) {
            throw new InvalidRequestException('Offer.externalId', 'must be a string');
        }

        RequestValidator::stringLength($this->externalId->value(), 0, 255, 'Offer.externalId');
    }

    /**
     * @param OptionalValue<mixed> $value
     * @param class-string $type
     */
    private function validateInstance(OptionalValue $value, string $type, string $fieldPath): void
    {
        if ($value->isDefined() && !$value->isNull() && !($value->value() instanceof $type)) {
            throw new InvalidRequestException($fieldPath, sprintf('must be an instance of %s', $type));
        }
    }
}
