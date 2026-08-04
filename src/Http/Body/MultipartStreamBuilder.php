<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/** Builds portable multipart/form-data streams using only PSR-7 abstractions. */
final class MultipartStreamBuilder
{
    public function __construct(private readonly StreamFactoryInterface $streamFactory)
    {
    }

    /** @param iterable<MultipartPart> $parts */
    public function build(iterable $parts, ?string $boundary = null): MultipartBody
    {
        $boundary ??= 'von-halsky-' . bin2hex(random_bytes(18));
        if (preg_match('/\A[0-9A-Za-z\'()+_,.\/:=?-]{1,70}\z/D', $boundary) !== 1) {
            throw new ConfigurationException('The multipart boundary is invalid.');
        }

        /** @var list<StreamInterface> $streams */
        $streams = [];
        foreach ($parts as $part) {
            $headers = sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"",
                $boundary,
                self::quote($part->name),
            );
            if ($part->filename !== null) {
                $headers .= sprintf('; filename="%s"', self::quote($part->filename));
            }
            $headers .= "\r\n";

            foreach ($part->headers as $name => $value) {
                $headers .= sprintf("%s: %s\r\n", $name, $value);
            }

            $streams[] = $this->streamFactory->createStream($headers . "\r\n");
            $streams[] = $part->contents;
            $streams[] = $this->streamFactory->createStream("\r\n");
        }
        $streams[] = $this->streamFactory->createStream(sprintf("--%s--\r\n", $boundary));

        return new MultipartBody(new MultipartStream($streams), $boundary);
    }

    private static function quote(string $value): string
    {
        return addcslashes($value, "\\\"");
    }
}
