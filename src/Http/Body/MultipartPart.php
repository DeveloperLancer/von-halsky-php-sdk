<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use Psr\Http\Message\StreamInterface;

/** A single stream-backed multipart form-data part. */
final class MultipartPart
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly string $name,
        public readonly StreamInterface $contents,
        public readonly ?string $filename = null,
        public readonly array $headers = [],
    ) {
        self::assertHeaderValue($name, 'part name');
        if ($filename !== null) {
            self::assertHeaderValue($filename, 'filename');
        }

        foreach ($headers as $header => $value) {
            if (preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/D', $header) !== 1) {
                throw new ConfigurationException('A multipart header name is invalid.');
            }
            self::assertHeaderValue($value, 'header value');
        }
    }

    private static function assertHeaderValue(string $value, string $label): void
    {
        if ($value === '' || str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new ConfigurationException(sprintf('The multipart %s is empty or contains a line break.', $label));
        }
    }
}
