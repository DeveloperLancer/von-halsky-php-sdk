<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use Psr\Http\Message\ResponseInterface;

/** Immutable response headers with case-insensitive lookup. */
final class ResponseHeaders
{
    /** @param array<string, list<string>> $headers */
    private function __construct(private readonly array $headers)
    {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = array_values($values);
        }

        return new self($headers);
    }

    /** @return list<string> */
    public function get(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function line(string $name): string
    {
        return implode(', ', $this->get($name));
    }

    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    /** @return array<string, list<string>> */
    public function all(): array
    {
        return $this->headers;
    }
}
