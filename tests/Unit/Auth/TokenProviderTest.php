<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Auth;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\RefreshingTokenProvider;
use DevLancer\VonHalsky\Auth\RefreshToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Auth\TokenContext;
use DevLancer\VonHalsky\Auth\TokenSet;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\Tests\Support\FrozenClock;
use DevLancer\VonHalsky\Tests\Support\TestLock;
use DevLancer\VonHalsky\Tests\Support\TestTokenStore;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class TokenProviderTest extends TestCase
{
    public function testStaticProviderReturnsExactOpaqueToken(): void
    {
        $token = new AccessToken('opaque-value', new DateTimeImmutable('2030-01-01T00:05:00+00:00'));

        self::assertSame($token, (new StaticTokenProvider($token))->getAccessToken());
    }

    public function testRefreshesWithinLeewayAndPersistsRotatedToken(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01T00:04:31+00:00'));
        $context = TokenContext::forEnvironment(Environment::stage(), 'client', 'merchant-1');
        $store = new TestTokenStore();
        $store->save($context, $this->tokens('old-access', 'old-refresh', '2030-01-01T00:05:00+00:00'));
        [$oauth, $http] = $this->oauth(
            $clock,
            new Response(200, [], '{"access_token":"new-access","refresh_token":"rotated-refresh","expires_in":300,"token_type":"Bearer","scope":"openid"}'),
            new Response(200, [], '{"access_token":"newer-access","refresh_token":"second-rotation","expires_in":300,"token_type":"Bearer","scope":"openid"}'),
        );
        $provider = new RefreshingTokenProvider(
            $context,
            $store,
            new TestLock(),
            $oauth,
            'placeholder-secret',
            $clock,
        );

        self::assertSame('new-access', $provider->getAccessToken()->value);
        self::assertSame('rotated-refresh', $store->load($context)?->refreshToken?->value);
        self::assertSame('new-access', $provider->getAccessToken()->value);
        self::assertCount(1, $http->requests());

        $clock->set(new DateTimeImmutable('2030-01-01T00:09:02+00:00'));
        self::assertSame('newer-access', $provider->getAccessToken()->value);
        parse_str((string) $http->requestAt(1)->getBody(), $secondRefresh);
        self::assertSame('rotated-refresh', $secondRefresh['refresh_token']);
        $stored = $store->load($context);
        self::assertNotNull($stored->refreshToken);
        self::assertSame('second-rotation', $stored->refreshToken->value);
    }

    public function testReReadsStoreAfterLockAndAvoidsDuplicateRefresh(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01T00:04:31+00:00'));
        $context = TokenContext::forEnvironment(Environment::stage(), 'client', 'merchant-1');
        $store = new TestTokenStore();
        $store->save($context, $this->tokens('stale-access', 'stale-refresh', '2030-01-01T00:05:00+00:00'));
        [$oauth, $http] = $this->oauth($clock);
        $newer = $this->tokens('already-refreshed', 'already-rotated', '2030-01-01T00:09:00+00:00');
        $lock = new TestCallbackLock(static function () use ($store, $context, $newer): void {
            $store->save($context, $newer);
        });
        $provider = new RefreshingTokenProvider(
            $context,
            $store,
            $lock,
            $oauth,
            'placeholder-secret',
            $clock,
        );

        self::assertSame('already-refreshed', $provider->getAccessToken()->value);
        self::assertCount(0, $http->requests());
    }

    public function testStoreSeparatesEnvironmentContexts(): void
    {
        $store = new TestTokenStore();
        $stage = TokenContext::forEnvironment(Environment::stage(), 'same-client', 'same-merchant');
        $production = TokenContext::forEnvironment(Environment::production(), 'same-client', 'same-merchant');
        $stageTokens = $this->tokens('stage-access', 'stage-refresh', '2030-01-01T00:05:00+00:00');
        $productionTokens = $this->tokens('production-access', 'production-refresh', '2030-01-01T00:05:00+00:00');

        $store->save($stage, $stageTokens);
        $store->save($production, $productionTokens);

        self::assertSame($stageTokens, $store->load($stage));
        self::assertSame($productionTokens, $store->load($production));
        self::assertNotSame($stage->storageKey(), $production->storageKey());
    }

    public function testRejectsMissingAndExpiredRefreshToken(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01T00:05:00+00:00'));
        $context = TokenContext::forEnvironment(Environment::stage(), 'client', 'merchant');
        $store = new TestTokenStore();
        $store->save($context, new TokenSet(
            new AccessToken('expired-access', new DateTimeImmutable('2030-01-01T00:04:00+00:00')),
            new RefreshToken('expired-refresh', new DateTimeImmutable('2030-01-01T00:05:00+00:00')),
            'Bearer',
            ['openid'],
            new DateTimeImmutable('2030-01-01T00:00:00+00:00'),
        ));
        [$oauth] = $this->oauth($clock);
        $provider = new RefreshingTokenProvider(
            $context,
            $store,
            new TestLock(),
            $oauth,
            'placeholder-secret',
            $clock,
        );

        $this->expectException(AuthenticationFlowException::class);
        $provider->getAccessToken();
    }

    private function tokens(string $access, string $refresh, string $expiresAt): TokenSet
    {
        return new TokenSet(
            new AccessToken($access, new DateTimeImmutable($expiresAt)),
            new RefreshToken($refresh),
            'Bearer',
            ['openid'],
            new DateTimeImmutable('2030-01-01T00:00:00+00:00'),
        );
    }

    /**
     * @param Response ...$responses
     * @return array{OAuthClient, FakeHttpClient}
     */
    private function oauth(FrozenClock $clock, Response ...$responses): array
    {
        $http = new FakeHttpClient(array_values($responses));
        $factory = new Psr17Factory();

        return [new OAuthClient(Environment::stage(), $http, $factory, $factory, $clock), $http];
    }
}
