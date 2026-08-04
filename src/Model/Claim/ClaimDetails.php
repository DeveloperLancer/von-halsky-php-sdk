<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Claim;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\ClaimId;

final class ClaimDetails implements ResponseDtoInterface
{
    /**
     * Claimant and order payloads may contain PII and monetary line details.
     *
     * @param array<string, mixed> $claimant
     * @param array<string, mixed> $relatedOrder
     * @param list<array<string, mixed>> $orderLines
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public readonly ClaimId $id,
        public readonly ClaimState $state,
        public readonly ?string $resolution,
        public readonly array $claimant,
        public readonly array $relatedOrder,
        public readonly array $orderLines,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
