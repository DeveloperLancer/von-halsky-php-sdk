<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** A category node; children contain only data included in the same response. */
final class Category implements ResponseDtoInterface
{
    /**
     * @param list<Category>        $children
     * @param list<CategoryRelation> $relations
     * @param array<string, string> $metadata
     * @param array<string, mixed>  $additionalData
     */
    public function __construct(
        public readonly CategoryId $id,
        public readonly string $name,
        public readonly bool $leaf,
        public readonly bool $doesNotRequireGpsrInfo,
        public readonly ?string $description,
        public readonly array $children = [],
        public readonly array $relations = [],
        public readonly array $metadata = [],
        private readonly array $additionalData = [],
    ) {
    }

    /** Ensures this category can be assigned to an offer. */
    public function requireLeaf(): self
    {
        if (!$this->leaf) {
            throw new InvalidRequestException('categoryId', 'must identify a leaf category');
        }

        return $this;
    }

    public function additionalData(): array
    {
        return $this->additionalData;
    }
}
