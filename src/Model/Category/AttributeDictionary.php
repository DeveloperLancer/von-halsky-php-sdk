<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

/** Dictionary of allowed values for a category attribute. */
final class AttributeDictionary implements ResponseDtoInterface
{
    /**
     * @param list<AttributeDictionaryOption> $options
     * @param array<string, mixed>             $additionalData
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $options,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
