<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Refreshes expiring access tokens under a context-specific lock. */
final class RefreshingTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly TokenContext $context,
        private readonly TokenStoreInterface $store,
        private readonly LockInterface $lock,
        private readonly OAuthClient $oauthClient,
        private readonly string $clientSecret,
        private readonly ClockInterface $clock,
        private readonly int $expiryLeewaySeconds = 30,
    ) {
        if ($clientSecret === '') {
            throw new AuthenticationFlowException('The OAuth client secret cannot be empty.');
        }
        if ($expiryLeewaySeconds < 0) {
            throw new AuthenticationFlowException('The token expiry leeway cannot be negative.');
        }
    }

    public function getAccessToken(): AccessToken
    {
        $tokens = $this->store->load($this->context);
        if ($tokens === null) {
            throw new AuthenticationFlowException('No token set is available for this context.');
        }
        if (!$tokens->accessToken->isExpiring($this->clock, $this->expiryLeewaySeconds)) {
            return $tokens->accessToken;
        }

        return $this->lock->synchronized($this->context, function (): AccessToken {
            $current = $this->store->load($this->context);
            if ($current === null) {
                throw new AuthenticationFlowException('No token set is available for this context.');
            }
            if (!$current->accessToken->isExpiring($this->clock, $this->expiryLeewaySeconds)) {
                return $current->accessToken;
            }
            if ($current->refreshToken === null || $current->refreshToken->isExpired($this->clock)) {
                throw new AuthenticationFlowException('No usable refresh token is available for this context.');
            }

            $refreshed = $this->oauthClient->refresh(
                $this->context->clientId,
                $this->clientSecret,
                $current->refreshToken,
                $current->scopes,
            );
            $this->store->save($this->context, $refreshed);

            return $refreshed->accessToken;
        });
    }
}
