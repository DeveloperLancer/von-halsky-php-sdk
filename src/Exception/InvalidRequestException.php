<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** Raised before network I/O when a request value violates the supported contract. */
final class InvalidRequestException extends VonHalskyException
{
    public function __construct(
        public readonly string $fieldPath,
        public readonly string $reason,
    ) {
        parent::__construct(sprintf('Invalid request field "%s": %s', $fieldPath, $reason));
    }
}
