<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;

final class OfferListOptions
{
    /**
     * @param list<string> $statuses
     * @param list<string> $sort
     */
    public function __construct(
        public readonly array $statuses = [],
        public readonly int $limit = 10,
        public readonly int $offset = 0,
        public readonly array $sort = [],
        public readonly ?ResponseLanguage $language = null,
    ) {
        RequestValidator::integerRange($limit, 0, 30, 'offers.limit');
        if ($offset < 0) {
            throw new InvalidRequestException('offers.offset', 'must be non-negative');
        }
        foreach ($sort as $value) {
            if (!in_array($value, ['status', '-status', 'updatedAt', '-updatedAt', 'createdAt', '-createdAt'], true)) {
                throw new InvalidRequestException('offers.sort', 'contains an unsupported value');
            }
        }
    }
}
