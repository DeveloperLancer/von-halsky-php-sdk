<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

/** Options for category details, including the number of descendant levels. */
final class CategoryDetailsOptions
{
    public function __construct(
        public readonly int $depth = 1,
        public readonly ?ResponseLanguage $language = null,
    ) {
        if ($depth < 0 || $depth > 10) {
            throw new InvalidRequestException('depth', 'must be between 0 and 10');
        }
    }
}
