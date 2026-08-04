<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

/** Always returns the immutable access token supplied by the application. */
final class StaticTokenProvider implements TokenProviderInterface
{
    public function __construct(private readonly AccessToken $accessToken)
    {
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }
}
