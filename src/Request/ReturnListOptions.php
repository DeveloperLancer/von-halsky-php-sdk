<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;

final class ReturnListOptions
{
    /** @param list<string> $statuses */
    public function __construct(
        public readonly array $statuses = [],
        public readonly int $limit = 10,
        public readonly int $offset = 0,
        public readonly ?ResponseLanguage $language = null,
    ) {
        RequestValidator::integerRange($limit, 0, 30, 'returns.limit');
        if ($offset < 0) {
            throw new InvalidRequestException('returns.offset', 'must be non-negative');
        }
    }
}
