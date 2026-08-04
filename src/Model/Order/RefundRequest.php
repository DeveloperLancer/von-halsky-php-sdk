<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\ValueObject\Money;

/** A precise partial refund. Omit this DTO from refund() to request a full refund. */
final class RefundRequest implements RequestDtoInterface
{
    public function __construct(public readonly Money $amount)
    {
    }

    public function jsonSerialize(): array
    {
        return ['amount' => ['amount' => $this->amount->minorUnits() / 100.0, 'currency' => $this->amount->currency->value]];
    }
}
