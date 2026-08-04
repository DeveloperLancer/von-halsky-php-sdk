<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;

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

    /**
     * @param OptionalValue<Price|null>|null    $price
     * @param OptionalValue<Stock|null>|null    $stock
     * @param OptionalValue<GpsrInfo|null>|null $gpsr
     * @param OptionalValue<int>|null      $daysToShip
     */
    public function __construct(?OptionalValue $price = null, ?OptionalValue $stock = null, ?OptionalValue $gpsr = null, ?OptionalValue $daysToShip = null)
    {
        $this->price = $price ?? OptionalValue::undefined();
        $this->stock = $stock ?? OptionalValue::undefined();
        $this->gpsr = $gpsr ?? OptionalValue::undefined();
        $this->daysToShip = $daysToShip ?? OptionalValue::undefined();
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
        ];
    }
}
