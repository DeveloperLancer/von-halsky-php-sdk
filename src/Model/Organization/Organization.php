<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Organization;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Immutable organization available to the current integration token. */
final class Organization implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly ?OrganizationId $id,
        public readonly ?string $name,
        public readonly ?string $status,
        public readonly ?string $type,
        public readonly ?string $logoUrl,
        public readonly ?string $operationalRegion,
        public readonly ?OrganizationParent $parent,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
