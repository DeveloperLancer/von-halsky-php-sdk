<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Internal\Http\RetryingHttpClient;
use DevLancer\VonHalsky\Internal\Http\SdkRetryingClientInterface;
use DevLancer\VonHalsky\Reliability\RetryPolicy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** The complete PSR-only dependency set needed by the HTTP layer. */
final class HttpClientDependencies
{
    public function __construct(
        public readonly ClientInterface $httpClient,
        public readonly RequestFactoryInterface $requestFactory,
        public readonly StreamFactoryInterface $streamFactory,
        public readonly bool $performsRetries = false,
    ) {
        if ($httpClient instanceof SdkRetryingClientInterface && !$performsRetries) {
            throw new ConfigurationException('Retrying HTTP dependencies must declare performsRetries: true.');
        }
    }

    /** Returns a new dependency set with explicit, bounded GET-only retry. */
    public function withRetry(?RetryPolicy $policy = null): self
    {
        if ($this->performsRetries) {
            throw new ConfigurationException('HTTP retry is already enabled for these dependencies.');
        }

        return new self(
            new RetryingHttpClient($this->httpClient, $policy ?? new RetryPolicy()),
            $this->requestFactory,
            $this->streamFactory,
            true,
        );
    }
}
