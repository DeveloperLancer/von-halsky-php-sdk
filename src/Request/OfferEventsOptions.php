<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\EventId;

final class OfferEventsOptions
{
    /** @param list<string> $types */
    public function __construct(
        public readonly ?EventId $untilId = null,
        public readonly array $types = [],
        public readonly int $limit = 100,
        public readonly ?ResponseLanguage $language = null,
    ) {
        RequestValidator::integerRange($limit, 0, 1000, 'offerEvents.limit');
    }
}
