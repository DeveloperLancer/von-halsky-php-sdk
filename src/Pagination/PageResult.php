<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Pagination;

/** @template T */
final class PageResult
{
    /** @var list<T> */
    public readonly array $items;

    /**
     * @param array<int, T>         $items
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        array $items,
        public readonly Page $page,
        public readonly array $additionalData = [],
    ) {
        if (!array_is_list($items)) {
            throw new \InvalidArgumentException('Page items must be a list.');
        }
        $this->items = $items;
    }
}
