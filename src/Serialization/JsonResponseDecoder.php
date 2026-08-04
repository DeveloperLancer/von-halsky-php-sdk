<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Serialization;

use DevLancer\VonHalsky\Exception\ResponseMappingException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/** Decodes successful JSON objects and delegates API failures to typed exceptions. */
final class JsonResponseDecoder
{
    public function __construct(private readonly ApiExceptionFactory $apiExceptions = new ApiExceptionFactory())
    {
    }

    /** @return array<string, mixed>|null */
    public function decodeObject(ResponseInterface $response, string $operationId): ?array
    {
        $decoded = $this->decode($response, $operationId);
        if ($decoded === null) {
            return null;
        }
        if (array_is_list($decoded)) {
            throw new ResponseMappingException('$', 'response root must be a JSON object');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /** @return list<mixed> */
    public function decodeList(ResponseInterface $response, string $operationId): array
    {
        $decoded = $this->decode($response, $operationId);
        if ($decoded === null || !array_is_list($decoded)) {
            throw new ResponseMappingException('$', 'response root must be a JSON array');
        }

        /** @var list<mixed> $decoded */
        return $decoded;
    }

    /** @return array<mixed>|null */
    private function decode(ResponseInterface $response, string $operationId): ?array
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw $this->apiExceptions->fromResponse($response, $operationId);
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            if ($status === 204 || $status === 205) {
                return null;
            }
            throw new ResponseMappingException('$', 'successful response body is empty');
        }

        try {
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ResponseMappingException('$', 'response body is not valid JSON');
        }
        if (!is_array($decoded)) {
            throw new ResponseMappingException('$', 'response root must be a JSON object or array');
        }

        return $decoded;
    }
}
