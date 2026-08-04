<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** Typed identity and lifecycle data plus the complete forward-compatible offer payload. */
final class OfferDetails implements ResponseDtoInterface
{
    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $stock
     * @param array<string, mixed> $price
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public readonly OfferId $id,
        public readonly OfferStatus $status,
        public readonly array $product,
        public readonly array $stock,
        public readonly array $price,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly array $metadata,
        public readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
