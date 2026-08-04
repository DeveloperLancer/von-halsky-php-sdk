<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** An accepted asynchronous command; it does not promise that an offer is ready. */
final class CommandHandle implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly CommandId $commandId,
        public readonly ?OfferId $offerId = null,
        public readonly ?string $externalId = null,
        public readonly ?string $status = null,
        public readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
