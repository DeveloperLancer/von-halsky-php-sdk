<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use DevLancer\VonHalsky\Auth\TokenProviderInterface;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Exception\InvalidTransportRequestException;
use DevLancer\VonHalsky\Exception\NetworkTransportException;
use DevLancer\VonHalsky\Exception\TransportException;
use DevLancer\VonHalsky\Http\Body\FormUrlencodedBodyEncoder;
use DevLancer\VonHalsky\Http\Body\JsonRequestBodyEncoder;
use DevLancer\VonHalsky\Http\Body\MultipartPart;
use DevLancer\VonHalsky\Http\Body\MultipartStreamBuilder;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/** @internal Executes absolute PSR-18 requests without transport-specific options. */
final class RequestExecutor
{
    public function __construct(
        private readonly Environment $environment,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?TokenProviderInterface $tokenProvider = null,
    ) {
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<string, string|list<string>>       $headers
     */
    public function execute(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        ?StreamInterface $body = null,
    ): ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $this->buildUri($path, $query));
        if (!array_key_exists('Accept', $headers) && !array_key_exists('accept', $headers)) {
            $request = $request->withHeader('Accept', 'application/json');
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($this->tokenProvider !== null) {
            $request = $request->withHeader(
                'Authorization',
                'Bearer ' . $this->tokenProvider->getAccessToken()->value,
            );
        }
        if ($body !== null) {
            $request = $request->withBody($body);
        }
        try {
            return $this->httpClient->sendRequest($request);
        } catch (NetworkExceptionInterface) {
            throw new NetworkTransportException('The HTTP request failed before a usable response was received.');
        } catch (RequestExceptionInterface) {
            throw new InvalidTransportRequestException('The HTTP client rejected the SDK request.');
        } catch (ClientExceptionInterface) {
            throw new TransportException('The HTTP client could not complete the SDK request.');
        }
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<string, string|list<string>>       $headers
     */
    public function executeJson(
        string $method,
        string $path,
        mixed $payload,
        array $query = [],
        array $headers = [],
    ): ResponseInterface {
        if (!array_key_exists('Content-Type', $headers) && !array_key_exists('content-type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        return $this->execute(
            $method,
            $path,
            $query,
            $headers,
            (new JsonRequestBodyEncoder($this->streamFactory))->encode($payload),
        );
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<string, string|list<string>>       $headers
     */
    public function executeDto(
        string $method,
        string $path,
        RequestDtoInterface $request,
        array $query = [],
        array $headers = [],
    ): ResponseInterface {
        return $this->executeJson(
            $method,
            $path,
            (new RequestNormalizer())->normalize($request),
            $query,
            $headers,
        );
    }

    /**
     * @param array<string, bool|float|int|string|null> $fields
     * @param array<string, string|list<string>>        $headers
     */
    public function executeForm(
        string $method,
        string $path,
        array $fields,
        array $headers = [],
    ): ResponseInterface {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->execute(
            $method,
            $path,
            [],
            $headers,
            (new FormUrlencodedBodyEncoder($this->streamFactory))->encode($fields),
        );
    }

    /**
     * @param iterable<MultipartPart>             $parts
     * @param array<string, string|list<string>> $headers
     * @param array<string, scalar|list<scalar>|null> $query
     */
    public function executeMultipart(
        string $method,
        string $path,
        iterable $parts,
        array $headers = [],
        array $query = [],
    ): ResponseInterface {
        $multipart = (new MultipartStreamBuilder($this->streamFactory))->build($parts);
        $headers['Content-Type'] = $multipart->contentType();

        return $this->execute($method, $path, $query, $headers, $multipart->stream);
    }

    /** @param array<string, scalar|list<scalar>|null> $query */
    private function buildUri(string $path, array $query): string
    {
        if (str_contains($path, '?') || str_contains($path, '#') || str_starts_with($path, '//')) {
            throw new ConfigurationException('A request path must not contain a host, query string or fragment.');
        }

        $uri = $this->environment->apiBaseUrl;
        if ($path !== '' && $path !== '/') {
            $uri .= '/' . ltrim($path, '/');
        }

        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $queryString === '' ? $uri : $uri . '?' . $queryString;
    }
}
