<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Supplies an access token immediately before an API request. */
interface TokenProviderInterface
{
    /** @throws AuthenticationFlowException */
    public function getAccessToken(): AccessToken;
}
