<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\ValueObject\OfferId;

final class OfferStockUpdate implements RequestDtoInterface
{
    public function __construct(public readonly OfferId $offerId, public readonly Stock $stock)
    {
    }

    public function jsonSerialize(): array
    {
        return ['offerId' => $this->offerId, 'stock' => $this->stock];
    }
}
