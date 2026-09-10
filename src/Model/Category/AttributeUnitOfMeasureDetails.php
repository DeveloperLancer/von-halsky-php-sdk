<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

/** Unit of measure in which a measurable category attribute value should be expressed. */
final class AttributeUnitOfMeasureDetails implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly string $code,
        public readonly string $symbol,
        public readonly string $group,
        public readonly string $translation,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
