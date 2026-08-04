<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\OfferId;

final class OfferEvent implements ResponseDtoInterface
{
    public function __construct(
        public readonly EventId $id,
        public readonly OfferEventType $type,
        public readonly OfferId $offerId,
        public readonly ?string $externalId,
        public readonly ?DateTimeImmutable $occurredAt,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
