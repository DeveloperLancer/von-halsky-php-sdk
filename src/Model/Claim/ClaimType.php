<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Claim;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\ClaimId;

final class ClaimType implements ResponseDtoInterface
{
    public function __construct(
        public readonly ClaimId $id,
        public readonly string $description,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
