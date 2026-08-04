<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use DevLancer\VonHalsky\Exception\SerializationException;
use JsonException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/** Serializes JSON request bodies without losing explicit null values. */
final class JsonRequestBodyEncoder
{
    public function __construct(private readonly StreamFactoryInterface $streamFactory)
    {
    }

    public function encode(mixed $value): StreamInterface
    {
        try {
            $json = json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException) {
            throw new SerializationException('The request body could not be encoded as JSON.');
        }

        return $this->streamFactory->createStream($json);
    }
}
