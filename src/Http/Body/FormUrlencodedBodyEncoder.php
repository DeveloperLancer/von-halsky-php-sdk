<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/** Encodes application/x-www-form-urlencoded bodies according to RFC 3986. */
final class FormUrlencodedBodyEncoder
{
    public function __construct(private readonly StreamFactoryInterface $streamFactory)
    {
    }

    /** @param array<string, bool|float|int|string|null> $fields */
    public function encode(array $fields): StreamInterface
    {
        return $this->streamFactory->createStream(http_build_query($fields, '', '&', PHP_QUERY_RFC3986));
    }
}
