<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

final class DeliveryMethod implements ResponseDtoInterface
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
