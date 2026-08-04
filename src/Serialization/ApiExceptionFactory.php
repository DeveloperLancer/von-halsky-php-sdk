<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Serialization;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\AuthenticationException;
use DevLancer\VonHalsky\Exception\AuthorizationException;
use DevLancer\VonHalsky\Exception\BadRequestException;
use DevLancer\VonHalsky\Exception\ConflictException;
use DevLancer\VonHalsky\Exception\ErrorDetail;
use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Exception\RateLimitException;
use DevLancer\VonHalsky\Exception\ServerException;
use DevLancer\VonHalsky\Exception\UnprocessableEntityException;
use DevLancer\VonHalsky\Http\RateLimit;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/** Maps RFC 7807-style API responses to the stable SDK exception hierarchy. */
final class ApiExceptionFactory
{
    public function fromResponse(ResponseInterface $response, string $operationId): ApiException
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = $this->decodeProblem($body);
        $validProblem = $payload !== null;
        $code = $payload === null ? null : $this->firstString($payload, ['errorCode', 'code', 'type']);
        $message = $payload === null
            ? sprintf('The API returned HTTP %d with an invalid error payload.', $status)
            : ($this->firstString($payload, ['errorMessage', 'detail', 'message', 'title'])
                ?? sprintf('The API returned HTTP %d.', $status));
        $details = $payload === null ? [] : $this->details($payload['details'] ?? $payload['errors'] ?? []);
        $headers = $this->safeHeaders($response);
        $correlationId = $response->getHeaderLine('X-Correlation-ID');
        if ($correlationId === '') {
            $correlationId = $response->getHeaderLine('X-Request-ID');
        }
        if ($correlationId === '') {
            $correlationId = null;
        }
        $arguments = [
            $status,
            $code,
            $message,
            $details,
            $headers,
            RateLimit::fromResponse($response),
            $correlationId,
            $operationId,
            $validProblem ? null : $this->redactedExcerpt($body),
        ];

        return match (true) {
            $status === 400 => new BadRequestException(...$arguments),
            $status === 401 => new AuthenticationException(...$arguments),
            $status === 403 => new AuthorizationException(...$arguments),
            $status === 404 => new NotFoundException(...$arguments),
            $status === 409 => new ConflictException(...$arguments),
            $status === 422 => new UnprocessableEntityException(...$arguments),
            $status === 429 => new RateLimitException(...$arguments),
            $status >= 500 && $status <= 599 => new ServerException(...$arguments),
            default => new ApiException(...$arguments),
        };
    }

    /** @return array<string, mixed>|null */
    private function decodeProblem(string $body): ?array
    {
        if ($body === '') {
            return null;
        }
        try {
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            return null;
        }
        /** @var array<string, mixed> $decoded */

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string>         $keys
     */
    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return null;
    }

    /** @return list<ErrorDetail> */
    private function details(mixed $raw): array
    {
        if (!is_array($raw) || !array_is_list($raw)) {
            return [];
        }
        $details = [];
        foreach ($raw as $item) {
            if (!is_array($item) || array_is_list($item)) {
                continue;
            }
            /** @var array<string, mixed> $item */
            $message = $this->firstString($item, ['message', 'errorMessage', 'detail']);
            if ($message === null) {
                continue;
            }
            $code = $this->firstString($item, ['code', 'errorCode']);
            $path = $this->firstString($item, ['field', 'fieldPath', 'path']);
            $details[] = new ErrorDetail(
                $code,
                $message,
                $path,
                array_diff_key($item, array_flip(['code', 'errorCode', 'message', 'errorMessage', 'detail', 'field', 'fieldPath', 'path'])),
            );
        }

        return $details;
    }

    /** @return array<string, list<string>> */
    private function safeHeaders(ResponseInterface $response): array
    {
        $safe = [];
        foreach (['Content-Type', 'Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset', 'X-Correlation-ID', 'X-Request-ID'] as $name) {
            $values = $response->getHeader($name);
            if ($values !== []) {
                $safe[$name] = array_values($values);
            }
        }

        return $safe;
    }

    private function redactedExcerpt(string $body): ?string
    {
        if ($body === '') {
            return null;
        }
        $excerpt = substr($body, 0, 256);
        $excerpt = preg_replace('/Bearer\s+[^\s"\']+/i', 'Bearer [redacted]', $excerpt) ?? '[redacted]';
        $excerpt = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $excerpt) ?? '[redacted]';
        $excerpt = preg_replace('/("(?:access_token|refresh_token|client_secret|authorization)"\s*:\s*")[^"]*/i', '$1[redacted]', $excerpt) ?? '[redacted]';

        return $excerpt;
    }
}
