<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** A server-described relation from one category to another. */
final class CategoryRelation implements ResponseDtoInterface
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly ?CategoryId $categoryId,
        public readonly ?string $relation,
        private readonly array $additionalData = [],
    ) {
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
