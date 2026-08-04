<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;

final class ClaimListOptions
{
    /** @param list<string> $resolutions
     *  @param list<string> $states
     *  @param list<string> $sort
     */
    public function __construct(
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhoneNumber = null,
        public readonly array $resolutions = [],
        public readonly array $states = [],
        public readonly ?UtcDateTime $submissionDateFrom = null,
        public readonly ?UtcDateTime $submissionDateTo = null,
        public readonly int $limit = 10,
        public readonly int $offset = 0,
        public readonly array $sort = [],
        public readonly ?ResponseLanguage $language = null,
    ) {
        RequestValidator::integerRange($limit, 0, 30, 'claims.limit');
        if ($offset < 0) {
            throw new InvalidRequestException('claims.offset', 'must be non-negative');
        }
        foreach ($sort as $value) {
            if (!in_array($value, ['expires_at', '-expires_at', 'state', '-state', 'submission_date', '-submission_date'], true)) {
                throw new InvalidRequestException('claims.sort', 'contains an unsupported value');
            }
        }
    }
}
