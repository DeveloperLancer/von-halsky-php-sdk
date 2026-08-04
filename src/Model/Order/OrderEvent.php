<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\OrderId;

final class OrderEvent implements ResponseDtoInterface
{
    public function __construct(
        public readonly EventId $id,
        public readonly OrderEventType $type,
        public readonly OrderId $orderId,
        public readonly ?DateTimeImmutable $occurredAt,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
