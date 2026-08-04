<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** One structured validation or command error returned by the API. */
final class ErrorDetail
{
    /** @param array<string, mixed> $additionalData */
    public function __construct(
        public readonly ?string $code,
        public readonly string $message,
        public readonly ?string $fieldPath = null,
        public readonly array $additionalData = [],
    ) {
    }
}
