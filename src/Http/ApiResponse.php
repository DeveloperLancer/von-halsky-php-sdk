<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * A typed API result together with transport metadata.
 *
 * @template T
 */
final class ApiResponse
{
    /** @param T $data */
    private function __construct(
        public readonly mixed $data,
        public readonly int $statusCode,
        public readonly ResponseHeaders $headers,
        public readonly ?RateLimit $rateLimit,
        public readonly ?string $correlationId,
    ) {
    }

    /**
     * @template TValue
     * @param TValue $data
     * @return self<TValue>
     */
    public static function fromResponse(mixed $data, ResponseInterface $response): self
    {
        $correlationId = $response->getHeaderLine('X-Correlation-ID');
        if ($correlationId === '') {
            $correlationId = $response->getHeaderLine('X-Request-ID');
        }

        return new self(
            $data,
            $response->getStatusCode(),
            ResponseHeaders::fromResponse($response),
            RateLimit::fromResponse($response),
            $correlationId === '' ? null : $correlationId,
        );
    }
}
