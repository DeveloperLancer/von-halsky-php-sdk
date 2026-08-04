<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\Money;

final class DepositType implements ResponseDtoInterface
{
    public function __construct(public readonly string $id, public readonly string $name, public readonly Money $price)
    {
    }

    public function additionalData(): array
    {
        return [];
    }
}
