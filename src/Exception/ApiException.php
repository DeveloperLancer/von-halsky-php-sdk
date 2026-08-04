<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

use DevLancer\VonHalsky\Http\RateLimit;

/** Base exception for a non-successful HTTP response returned by the API. */
class ApiException extends VonHalskyException
{
    /**
     * @param list<ErrorDetail>        $details
     * @param array<string, list<string>> $safeHeaders
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly ?string $errorCode,
        string $message,
        public readonly array $details,
        public readonly array $safeHeaders,
        public readonly ?RateLimit $rateLimit,
        public readonly ?string $correlationId,
        public readonly string $operationId,
        public readonly ?string $invalidBodyExcerpt = null,
    ) {
        parent::__construct($message);
    }
}
