<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\Money;

final class RefundResult implements ResponseDtoInterface
{
    public function __construct(
        public readonly ?Money $amount,
        public readonly RefundStatus $status,
        public readonly ?string $description = null,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
