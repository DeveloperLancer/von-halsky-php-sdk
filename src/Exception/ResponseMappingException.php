<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** Raised when a successful response cannot be mapped to the documented model. */
final class ResponseMappingException extends SerializationException
{
    public function __construct(public readonly string $fieldPath, string $reason)
    {
        parent::__construct(sprintf('Unable to map response field "%s": %s', $fieldPath, $reason));
    }
}
