<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateInterval;
use DateTimeImmutable;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;
use DevLancer\VonHalsky\Http\Body\FormUrlencodedBodyEncoder;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Executes Von Halsky OAuth2 Authorization Code, Client Credentials, and refresh flows. */
final class OAuthClient
{
    private readonly ClockInterface $clock;

    public function __construct(
        private readonly Environment $environment,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    /**
     * @param list<OAuthScope> $scopes
     * @throws AuthenticationFlowException
     */
    public function createAuthorizationRequest(
        string $clientId,
        string $redirectUri,
        array $scopes,
    ): AuthorizationRequest {
        self::assertNotEmpty($clientId, 'OAuth client ID');
        self::assertRedirectUri($redirectUri);
        self::assertScopes($scopes);

        $pkce = PkcePair::generate();
        $state = self::base64UrlEncode(random_bytes(32));
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => self::scopeString($scopes),
            'state' => $state,
            'code_challenge' => $pkce->challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return new AuthorizationRequest(
            $this->environment->authorizationUrl . '?' . $query,
            $state,
            $pkce->verifier,
            $pkce->challenge,
            $redirectUri,
            $scopes,
        );
    }

    /**
     * Validates the callback values retained by the application before code exchange.
     *
     * @throws AuthenticationFlowException
     */
    public static function assertAuthorizationCallback(
        string $expectedState,
        string $receivedState,
        string $expectedRedirectUri,
        string $receivedRedirectUri,
    ): void {
        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            throw new AuthenticationFlowException('The OAuth callback state does not match the initiated flow.');
        }
        self::assertRedirectUri($expectedRedirectUri);
        self::assertRedirectUri($receivedRedirectUri);
        if (!hash_equals($expectedRedirectUri, $receivedRedirectUri)) {
            throw new AuthenticationFlowException('The OAuth callback redirect URI does not match the initiated flow.');
        }
    }

    /**
     * @throws AuthenticationFlowException
     */
    public function exchangeAuthorizationCode(
        string $clientId,
        string $authorizationCode,
        string $redirectUri,
        string $codeVerifier,
        ?string $clientSecret = null,
    ): TokenSet {
        self::assertNotEmpty($clientId, 'OAuth client ID');
        self::assertNotEmpty($authorizationCode, 'Authorization code');
        self::assertRedirectUri($redirectUri);
        PkcePair::fromVerifier($codeVerifier);
        if ($clientSecret !== null) {
            self::assertNotEmpty($clientSecret, 'OAuth client secret');
        }

        $fields = [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'code' => $authorizationCode,
            'redirect_uri' => $redirectUri,
            'code_verifier' => $codeVerifier,
        ];
        if ($clientSecret !== null) {
            $fields['client_secret'] = $clientSecret;
        }

        return $this->requestToken($fields);
    }

    /**
     * This grant is intended only for merchants acting on their own behalf.
     *
     * @param list<OAuthScope> $scopes
     * @throws AuthenticationFlowException
     */
    public function requestClientCredentialsToken(
        string $clientId,
        string $clientSecret,
        array $scopes,
    ): TokenSet {
        self::assertNotEmpty($clientId, 'OAuth client ID');
        self::assertNotEmpty($clientSecret, 'OAuth client secret');
        self::assertScopes($scopes);

        return $this->requestToken([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => self::scopeString($scopes),
        ], self::scopeValues($scopes));
    }

    /**
     * @param list<string> $fallbackScopes
     * @throws AuthenticationFlowException
     */
    public function refresh(
        string $clientId,
        string $clientSecret,
        RefreshToken $refreshToken,
        array $fallbackScopes = [],
    ): TokenSet {
        self::assertNotEmpty($clientId, 'OAuth client ID');
        self::assertNotEmpty($clientSecret, 'OAuth client secret');

        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken->value,
        ], $fallbackScopes, $refreshToken);
    }

    /**
     * @param array<string, string> $fields
     * @param list<string>          $fallbackScopes
     */
    private function requestToken(
        array $fields,
        array $fallbackScopes = [],
        ?RefreshToken $fallbackRefreshToken = null,
    ): TokenSet {
        $request = $this->requestFactory
            ->createRequest('POST', $this->environment->tokenUrl)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody((new FormUrlencodedBodyEncoder($this->streamFactory))->encode($fields));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface) {
            throw new AuthenticationFlowException('The OAuth token endpoint could not be reached.');
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new AuthenticationFlowException('The OAuth token endpoint rejected the request.');
        }

        return $this->parseTokenResponse($response, $fallbackScopes, $fallbackRefreshToken);
    }

    /**
     * @param list<string> $fallbackScopes
     */
    private function parseTokenResponse(
        ResponseInterface $response,
        array $fallbackScopes,
        ?RefreshToken $fallbackRefreshToken,
    ): TokenSet {
        try {
            $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AuthenticationFlowException('The OAuth token endpoint returned invalid JSON.');
        }

        if (!is_array($payload)) {
            throw new AuthenticationFlowException('The OAuth token endpoint returned an invalid token response.');
        }

        $accessToken = $payload['access_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? null;
        $tokenType = $payload['token_type'] ?? null;
        if (!is_string($accessToken) || $accessToken === ''
            || !is_int($expiresIn) || $expiresIn <= 0
            || !is_string($tokenType) || $tokenType === ''
        ) {
            throw new AuthenticationFlowException('The OAuth token endpoint returned an incomplete token response.');
        }

        $receivedAt = $this->clock->now();
        $refreshToken = $this->parseRefreshToken($payload, $receivedAt, $fallbackRefreshToken);
        $scopes = $this->parseScopes($payload, $fallbackScopes);

        return new TokenSet(
            new AccessToken($accessToken, self::addSeconds($receivedAt, $expiresIn)),
            $refreshToken,
            $tokenType,
            $scopes,
            $receivedAt,
        );
    }

    /** @param array<mixed> $payload */
    private function parseRefreshToken(
        array $payload,
        DateTimeImmutable $receivedAt,
        ?RefreshToken $fallback,
    ): ?RefreshToken {
        if (!array_key_exists('refresh_token', $payload)) {
            return $fallback;
        }
        if (!is_string($payload['refresh_token']) || $payload['refresh_token'] === '') {
            throw new AuthenticationFlowException('The OAuth token endpoint returned an invalid refresh token.');
        }

        $expiresAt = null;
        if (array_key_exists('refresh_expires_in', $payload)) {
            if (!is_int($payload['refresh_expires_in']) || $payload['refresh_expires_in'] <= 0) {
                throw new AuthenticationFlowException('The OAuth token endpoint returned an invalid refresh expiry.');
            }
            $expiresAt = self::addSeconds($receivedAt, $payload['refresh_expires_in']);
        }

        return new RefreshToken($payload['refresh_token'], $expiresAt);
    }

    /**
     * @param array<mixed> $payload
     * @param list<string> $fallback
     * @return list<string>
     */
    private function parseScopes(array $payload, array $fallback): array
    {
        if (!array_key_exists('scope', $payload)) {
            return $fallback;
        }
        if (!is_string($payload['scope'])) {
            throw new AuthenticationFlowException('The OAuth token endpoint returned invalid scopes.');
        }
        if ($payload['scope'] === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $payload['scope']), static fn (string $scope): bool => $scope !== ''));
    }

    /** @param list<OAuthScope> $scopes */
    private static function assertScopes(array $scopes): void
    {
        if ($scopes === []) {
            throw new AuthenticationFlowException('At least one OAuth scope is required.');
        }
    }

    /** @param list<OAuthScope> $scopes */
    private static function scopeString(array $scopes): string
    {
        return implode(' ', self::scopeValues($scopes));
    }

    /**
     * @param list<OAuthScope> $scopes
     * @return list<string>
     */
    private static function scopeValues(array $scopes): array
    {
        return array_map(static fn (OAuthScope $scope): string => $scope->value, $scopes);
    }

    private static function assertNotEmpty(string $value, string $label): void
    {
        if ($value === '') {
            throw new AuthenticationFlowException($label . ' cannot be empty.');
        }
    }

    private static function assertRedirectUri(string $uri): void
    {
        $parts = parse_url($uri);
        if (filter_var($uri, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new AuthenticationFlowException('The OAuth redirect URI must be an absolute URL without userinfo or a fragment.');
        }

        $scheme = strtolower($parts['scheme']);
        $host = trim(strtolower($parts['host']), '[]');
        if ($scheme !== 'https' && !($scheme === 'http' && ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1'))) {
            throw new AuthenticationFlowException('The OAuth redirect URI must use HTTPS except on a loopback host.');
        }
    }

    private static function addSeconds(DateTimeImmutable $time, int $seconds): DateTimeImmutable
    {
        return $time->add(new DateInterval('PT' . $seconds . 'S'));
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
