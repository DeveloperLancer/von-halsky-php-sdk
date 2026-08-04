<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Stock quantity and unit. */
final class Stock implements RequestDtoInterface
{
    public function __construct(public readonly int $quantity, public readonly StockUnit $unit = StockUnit::UNIT)
    {
        RequestValidator::integerRange($quantity, 0, 999999, 'Stock.quantity');
    }

    public function jsonSerialize(): array
    {
        return ['quantity' => $this->quantity, 'unit' => $this->unit];
    }
}
