<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Pagination;

use DevLancer\VonHalsky\Exception\ResponseMappingException;

/** Immutable offset pagination metadata returned by the API. */
final class Page
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly int $offset,
        public readonly int $limit,
        public readonly int $total,
        public readonly array $additionalData = [],
    ) {
        if ($offset < 0 || $limit < 1 || $total < 0) {
            throw new ResponseMappingException('page', 'offset and total must be non-negative and limit must be positive');
        }
    }
}
