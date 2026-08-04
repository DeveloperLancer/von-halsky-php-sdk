<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** Filters for browsing a bounded part of the category tree. */
final class CategoryTreeOptions
{
    public function __construct(
        public readonly int $depth = 1,
        public readonly ?CategoryId $root = null,
        public readonly ?ResponseLanguage $language = null,
    ) {
        if ($depth < 0 || $depth > 10) {
            throw new InvalidRequestException('depth', 'must be between 0 and 10');
        }
    }
}
