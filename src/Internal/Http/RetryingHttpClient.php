<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal\Http;

use DevLancer\VonHalsky\Auth\ClockInterface;
use DevLancer\VonHalsky\Auth\SystemClock;
use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Http\RateLimit;
use DevLancer\VonHalsky\Reliability\RetryPolicy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** @internal Explicit PSR-18 decorator for short, bounded GET retries. */
final class RetryingHttpClient implements SdkRetryingClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RetryPolicy $policy = new RetryPolicy(),
        private readonly ClockInterface $clock = new SystemClock(),
        private readonly SleeperInterface $sleeper = new NativeSleeper(),
        private readonly JitterInterface $jitter = new FullJitter(),
    ) {
        if ($client instanceof SdkRetryingClientInterface) {
            throw new ConfigurationException('SDK retry cannot wrap another SDK retry client.');
        }
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $started = $this->timestamp();

        for ($attempt = 1; ; ++$attempt) {
            try {
                $response = $this->client->sendRequest($request);
            } catch (NetworkExceptionInterface $exception) {
                if (!$this->canRetry($request, $attempt, $started)) {
                    throw $exception;
                }
                $delay = $this->backoff($attempt);
                if (!$this->fitsDeadline($started, $delay)) {
                    throw $exception;
                }
                $this->sleeper->sleep($delay);
                continue;
            }

            if (!$this->retryableStatus($response->getStatusCode()) || !$this->canRetry($request, $attempt, $started)) {
                return $response;
            }

            $rateLimit = RateLimit::fromResponse($response, $this->clock->now());
            $delay = $this->responseDelay($response, $attempt, $rateLimit);
            if (!$this->fitsDeadline($started, $delay)) {
                return $response;
            }
            $this->sleeper->sleep($delay);
        }
    }

    private function canRetry(RequestInterface $request, int $attempt, float $started): bool
    {
        return strtoupper($request->getMethod()) === 'GET'
            && $attempt < $this->policy->maxAttempts
            && $this->timestamp() - $started < $this->policy->maximumElapsedSeconds;
    }

    private function retryableStatus(int $status): bool
    {
        return $status === 429 || in_array($status, [502, 503, 504], true);
    }

    private function responseDelay(ResponseInterface $response, int $attempt, ?RateLimit $rateLimit): float
    {
        if ($response->getStatusCode() === 429 && $rateLimit?->retryAt !== null) {
            return max(0.0, (float) ($rateLimit->retryAt->format('U.u') - $this->clock->now()->format('U.u')));
        }

        return $this->backoff($attempt);
    }

    private function backoff(int $attempt): float
    {
        $maximum = min(
            $this->policy->maximumDelaySeconds,
            $this->policy->baseDelaySeconds * (2 ** ($attempt - 1)),
        );

        return min($maximum, max(0.0, $this->jitter->apply($maximum)));
    }

    private function fitsDeadline(float $started, float $delay): bool
    {
        return $this->timestamp() - $started + $delay <= $this->policy->maximumElapsedSeconds;
    }

    private function timestamp(): float
    {
        return (float) $this->clock->now()->format('U.u');
    }
}
