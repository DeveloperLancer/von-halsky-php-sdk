<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Organization;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Immutable parent organization returned by the API. */
final class OrganizationParent implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly ?OrganizationId $id,
        public readonly ?string $name,
        public readonly ?string $status,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
