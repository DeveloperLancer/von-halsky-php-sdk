<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Auth;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Auth\PkcePair;
use DevLancer\VonHalsky\Auth\RefreshToken;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\Tests\Support\FakeNetworkException;
use DevLancer\VonHalsky\Tests\Support\FrozenClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OAuthClientTest extends TestCase
{
    private const VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    public function testBuildsAuthorizationUrlWithPkceAndRfc3986Scope(): void
    {
        [$oauth] = $this->oauth();

        $authorization = $oauth->createAuthorizationRequest(
            'client id/with spaces',
            'https://merchant.example/callback?channel=web',
            [OAuthScope::OpenId, OAuthScope::OffersRead],
        );

        self::assertStringStartsWith('https://stage-account.inpost-group.com/oauth2/authorize?', $authorization->authorizationUrl);
        parse_str((string) parse_url($authorization->authorizationUrl, PHP_URL_QUERY), $query);
        self::assertSame('code', $query['response_type']);
        self::assertSame('client id/with spaces', $query['client_id']);
        self::assertSame('openid api:offers:read', $query['scope']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame($authorization->state, $query['state']);
        self::assertSame($authorization->codeChallenge, $query['code_challenge']);
        self::assertSame($authorization->codeChallenge, PkcePair::fromVerifier($authorization->codeVerifier)->challenge);
    }

    public function testValidatesStateAndExactRedirectUri(): void
    {
        OAuthClient::assertAuthorizationCallback(
            'retained-state',
            'retained-state',
            'https://merchant.example/callback',
            'https://merchant.example/callback',
        );

        $this->expectNotToPerformAssertions();
    }

    /** @param array{string, string, string, string} $callback */
    #[DataProvider('invalidCallbackProvider')]
    public function testRejectsInvalidCallback(array $callback): void
    {
        $this->expectException(AuthenticationFlowException::class);

        OAuthClient::assertAuthorizationCallback(...$callback);
    }

    /** @return iterable<string, array{array{string, string, string, string}}> */
    public static function invalidCallbackProvider(): iterable
    {
        yield 'state mismatch' => [['expected', 'received', 'https://example.test/callback', 'https://example.test/callback']];
        yield 'redirect mismatch' => [['same', 'same', 'https://example.test/a', 'https://example.test/b']];
        yield 'fragment' => [['same', 'same', 'https://example.test/callback', 'https://example.test/callback#code']];
    }

    public function testExchangesAuthorizationCodeUsingStageEndpoint(): void
    {
        [$oauth, $http] = $this->oauth(new Response(200, [], json_encode([
            'access_token' => 'access-value',
            'refresh_token' => 'refresh-value',
            'expires_in' => 300,
            'refresh_expires_in' => 2592000,
            'token_type' => 'Bearer',
            'scope' => 'openid api:offers:read',
        ], JSON_THROW_ON_ERROR)));

        $tokens = $oauth->exchangeAuthorizationCode(
            'client-id',
            'one-time-code',
            'https://merchant.example/callback',
            self::VERIFIER,
        );

        $request = $http->requests()[0];
        self::assertSame('https://stage-account.inpost-group.com/oauth2/token', (string) $request->getUri());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        parse_str((string) $request->getBody(), $form);
        self::assertSame('authorization_code', $form['grant_type']);
        self::assertSame('one-time-code', $form['code']);
        self::assertSame(self::VERIFIER, $form['code_verifier']);
        self::assertArrayNotHasKey('client_secret', $form);
        self::assertSame('access-value', $tokens->accessToken->value);
        self::assertSame('refresh-value', $tokens->refreshToken?->value);
        self::assertSame('2030-01-01T00:05:00+00:00', $tokens->accessToken->expiresAt->format(DATE_ATOM));
        self::assertSame(['openid', 'api:offers:read'], $tokens->scopes);
    }

    public function testEncodesClientCredentialsScopes(): void
    {
        [$oauth, $http] = $this->oauth(new Response(200, [], '{"access_token":"access","expires_in":300,"token_type":"Bearer"}'));

        $tokens = $oauth->requestClientCredentialsToken(
            'merchant-client',
            'placeholder-client-secret',
            [OAuthScope::CategoriesRead, OAuthScope::OrdersWrite],
        );

        parse_str((string) $http->requests()[0]->getBody(), $form);
        self::assertSame('client_credentials', $form['grant_type']);
        self::assertSame('api:categories:read api:orders:write', $form['scope']);
        self::assertSame(['api:categories:read', 'api:orders:write'], $tokens->scopes);
        self::assertNull($tokens->refreshToken);
    }

    public function testKeepsPriorRefreshTokenWhenEndpointDoesNotRotateIt(): void
    {
        [$oauth] = $this->oauth(new Response(200, [], '{"access_token":"next-access","expires_in":300,"token_type":"Bearer"}'));
        $prior = new RefreshToken('prior-refresh');

        $tokens = $oauth->refresh('client', 'placeholder-secret', $prior, ['openid']);

        self::assertSame($prior, $tokens->refreshToken);
        self::assertSame(['openid'], $tokens->scopes);
    }

    public function testDoesNotLeakSecretsFromRejectedOrNetworkResponses(): void
    {
        [$rejected] = $this->oauth(new Response(400, [], '{"error_description":"server-secret-value"}'));

        try {
            $rejected->requestClientCredentialsToken('client', 'request-secret-value', [OAuthScope::OpenId]);
            self::fail('Expected the token endpoint to reject the request.');
        } catch (AuthenticationFlowException $exception) {
            self::assertStringNotContainsString('server-secret-value', $exception->getMessage());
            self::assertStringNotContainsString('request-secret-value', $exception->getMessage());
        }

        $factory = new Psr17Factory();
        $transportRequest = $factory->createRequest('POST', 'https://example.test?code=transport-secret-value');
        [$network] = $this->oauth(new FakeNetworkException($transportRequest, 'transport-secret-value'));

        try {
            $network->requestClientCredentialsToken('client', 'request-secret-value', [OAuthScope::OpenId]);
            self::fail('Expected a network failure.');
        } catch (AuthenticationFlowException $exception) {
            self::assertStringNotContainsString('transport-secret-value', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    /**
     * @param Response|FakeNetworkException ...$results
     * @return array{OAuthClient, FakeHttpClient}
     */
    private function oauth(Response|FakeNetworkException ...$results): array
    {
        $http = new FakeHttpClient(array_values($results));
        $factory = new Psr17Factory();
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01T00:00:00+00:00'));

        return [new OAuthClient(Environment::stage(), $http, $factory, $factory, $clock), $http];
    }
}
