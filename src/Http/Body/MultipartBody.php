<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use Psr\Http\Message\StreamInterface;

/** A generated multipart body and its matching Content-Type value. */
final class MultipartBody
{
    public function __construct(
        public readonly StreamInterface $stream,
        public readonly string $boundary,
    ) {
    }

    public function contentType(): string
    {
        return 'multipart/form-data; boundary=' . $this->boundary;
    }
}
