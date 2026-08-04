<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

/** One localized value allowed by an attribute dictionary. */
final class AttributeDictionaryOption implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly string $id,
        public readonly string $value,
        public readonly bool $active,
        public readonly ?string $language,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
