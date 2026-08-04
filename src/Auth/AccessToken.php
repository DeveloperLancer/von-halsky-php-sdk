<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Opaque access token with an absolute expiry time. */
final class AccessToken
{
    public function __construct(
        public readonly string $value,
        public readonly DateTimeImmutable $expiresAt,
    ) {
        if ($value === '') {
            throw new AuthenticationFlowException('An access token cannot be empty.');
        }
    }

    public function isExpiring(ClockInterface $clock, int $leewaySeconds = 30): bool
    {
        if ($leewaySeconds < 0) {
            throw new AuthenticationFlowException('The token expiry leeway cannot be negative.');
        }

        return $this->expiresAt->getTimestamp() <= $clock->now()->getTimestamp() + $leewaySeconds;
    }
}
