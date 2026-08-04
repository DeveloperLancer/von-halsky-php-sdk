<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Opaque refresh token with an optional server-provided expiry time. */
final class RefreshToken
{
    public function __construct(
        public readonly string $value,
        public readonly ?DateTimeImmutable $expiresAt = null,
    ) {
        if ($value === '') {
            throw new AuthenticationFlowException('A refresh token cannot be empty.');
        }
    }

    public function isExpired(ClockInterface $clock): bool
    {
        return $this->expiresAt !== null
            && $this->expiresAt->getTimestamp() <= $clock->now()->getTimestamp();
    }
}
