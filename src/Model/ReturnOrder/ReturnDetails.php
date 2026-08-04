<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\ReturnOrder;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\ReturnId;

final class ReturnDetails implements ResponseDtoInterface
{
    /**
     * Client data may contain PII and must not be logged verbatim.
     *
     * @param array<string, mixed> $client
     * @param list<array<string, mixed>> $orderLines
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public readonly ReturnId $id,
        public readonly OrderId $orderId,
        public readonly ReturnStatus $status,
        public readonly array $client,
        public readonly array $orderLines,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $deliveredAt,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
