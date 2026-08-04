<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

/** Attribute definition applicable to offers in a category. */
final class AttributeDefinition implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly AttributeType $type,
        public readonly AttributeExpectedValue $expectedValue,
        public readonly ?string $description,
        public readonly ?string $language,
        public readonly ?AttributeDictionary $dictionary,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
