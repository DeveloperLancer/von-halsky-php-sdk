<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Order identity and monetary totals with the complete forward-compatible payload. */
final class OrderDetails implements ResponseDtoInterface
{
    /**
     * Customer email is a platform hashmail. Invoice and delivery data may contain PII.
     *
     * @param list<array<string, mixed>> $orderLines
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $delivery
     * @param array<string, mixed>|null $invoice
     * @param array<string, mixed> $paymentDetails
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public readonly OrderId $id,
        public readonly OrganizationId $organizationId,
        public readonly OrderStatus $status,
        public readonly Money $finalPrice,
        public readonly Money $basePrice,
        public readonly array $orderLines,
        public readonly array $customer,
        public readonly array $delivery,
        public readonly ?array $invoice,
        public readonly array $paymentDetails,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
