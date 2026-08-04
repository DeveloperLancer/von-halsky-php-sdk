<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Immutable result of an OAuth token response. */
final class TokenSet
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly AccessToken $accessToken,
        public readonly ?RefreshToken $refreshToken,
        public readonly string $tokenType,
        public readonly array $scopes,
        public readonly DateTimeImmutable $receivedAt,
    ) {
        if ($tokenType === '') {
            throw new AuthenticationFlowException('The OAuth token type cannot be empty.');
        }
        foreach ($scopes as $scope) {
            if ($scope === '') {
                throw new AuthenticationFlowException('OAuth scopes cannot contain an empty value.');
            }
        }
    }
}
