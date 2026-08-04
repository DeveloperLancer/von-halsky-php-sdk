<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;

final class OfferPriceUpdate implements RequestDtoInterface
{
    public function __construct(public readonly OfferId $offerId, public readonly Money $price)
    {
    }

    public function jsonSerialize(): array
    {
        return ['offerId' => $this->offerId, 'price' => $this->price];
    }
}
