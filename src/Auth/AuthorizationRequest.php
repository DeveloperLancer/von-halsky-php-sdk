<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

/** Values that an application must retain while an Authorization Code flow is in progress. */
final class AuthorizationRequest
{
    /**
     * @param list<OAuthScope> $scopes
     */
    public function __construct(
        public readonly string $authorizationUrl,
        public readonly string $state,
        public readonly string $codeVerifier,
        public readonly string $codeChallenge,
        public readonly string $redirectUri,
        public readonly array $scopes,
    ) {
    }
}
