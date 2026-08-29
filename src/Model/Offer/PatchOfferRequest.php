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

    /**
     * @param OptionalValue<Price|null>|null    $price
     * @param OptionalValue<Stock|null>|null    $stock
     * @param OptionalValue<GpsrInfo|null>|null $gpsr
     * @param OptionalValue<int>|null           $daysToShip
     * @param OptionalValue<list<OfferImage>|null>|null $images
     */
    public function __construct(
        ?OptionalValue $price = null,
        ?OptionalValue $stock = null,
        ?OptionalValue $gpsr = null,
        ?OptionalValue $daysToShip = null,
        ?OptionalValue $images = null,
    ) {
        $this->price = $price ?? OptionalValue::undefined();
        $this->stock = $stock ?? OptionalValue::undefined();
        $this->gpsr = $gpsr ?? OptionalValue::undefined();
        $this->daysToShip = $daysToShip ?? OptionalValue::undefined();
        $this->images = $images ?? OptionalValue::undefined();
        if ($this->images->isDefined() && !$this->images->isNull()) {
            $imageValues = $this->images->value();
            if (!is_array($imageValues)) {
                throw new InvalidRequestException('Offer.images', 'must be a list');
            }
            RequestValidator::offerImages($imageValues);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'price' => $this->price,
            'stock' => $this->stock,
            'gpsr' => $this->gpsr,
            'shippingTime' => !$this->daysToShip->isDefined() ? OptionalValue::undefined() : OptionalValue::of(
                $this->daysToShip->isNull() ? null : ['daysToShip' => $this->daysToShip->value()],
            ),
            'images' => $this->images,
        ];
    }
}
