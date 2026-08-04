<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Reliability;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Internal\Http\RetryingHttpClient;
use DevLancer\VonHalsky\Reliability\RetryPolicy;
use DevLancer\VonHalsky\Tests\Support\AdvancingSleeper;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\Tests\Support\FakeNetworkException;
use DevLancer\VonHalsky\Tests\Support\FixedJitter;
use DevLancer\VonHalsky\Tests\Support\FrozenClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryingHttpClientTest extends TestCase
{
    public function testRetriesSafeGetForNetworkAndRateLimitFailures(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createRequest('GET', 'https://example.test/items');
        $clock = new FrozenClock(new DateTimeImmutable('2026-08-05T10:00:00Z'));
        $sleeper = new AdvancingSleeper($clock);
        $base = new FakeHttpClient([
            new FakeNetworkException($request, 'network failure'),
            new Response(429, ['Retry-After' => '0', 'X-RateLimit-Remaining' => '0']),
            new Response(200),
        ]);
        $client = new RetryingHttpClient(
            $base,
            new RetryPolicy(3, 0.1, 0.5, 1.0),
            $clock,
            $sleeper,
            new FixedJitter(),
        );

        self::assertSame(200, $client->sendRequest($request)->getStatusCode());
        self::assertSame([0.1, 0.0], $sleeper->delays);
        self::assertCount(3, $base->requests());
    }

    public function testNeverReplaysUnsafeMethods(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createRequest('POST', 'https://example.test/items');
        $base = new FakeHttpClient([new Response(503), new Response(200)]);
        $clock = new FrozenClock(new DateTimeImmutable('2026-08-05T10:00:00Z'));
        $client = new RetryingHttpClient($base, new RetryPolicy(), $clock, new AdvancingSleeper($clock), new FixedJitter());

        self::assertSame(503, $client->sendRequest($request)->getStatusCode());
        self::assertCount(1, $base->requests());
    }

    public function testElapsedLimitAndDoubleRetryAreEnforced(): void
    {
        $factory = new Psr17Factory();
        $clock = new FrozenClock(new DateTimeImmutable('2026-08-05T10:00:00Z'));
        $base = new FakeHttpClient([new Response(503), new Response(200)]);
        $client = new RetryingHttpClient($base, new RetryPolicy(3, 2.0, 2.0, 1.0), $clock, new AdvancingSleeper($clock), new FixedJitter());
        self::assertSame(503, $client->sendRequest($factory->createRequest('GET', 'https://example.test'))->getStatusCode());
        self::assertCount(1, $base->requests());

        $this->expectException(ConfigurationException::class);
        new RetryingHttpClient($client, new RetryPolicy(), $clock, new AdvancingSleeper($clock));
    }
}
