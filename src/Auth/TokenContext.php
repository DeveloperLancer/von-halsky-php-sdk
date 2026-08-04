<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Identifies one environment-, client-, and subject-specific token set. */
final class TokenContext
{
    public function __construct(
        public readonly string $environmentId,
        public readonly string $clientId,
        public readonly string $subject,
        public readonly ?string $organizationId = null,
    ) {
        foreach ([$environmentId, $clientId, $subject] as $value) {
            if ($value === '') {
                throw new AuthenticationFlowException('Token context values cannot be empty.');
            }
        }
        if ($organizationId === '') {
            throw new AuthenticationFlowException('A token context organization ID cannot be empty.');
        }
    }

    public static function forEnvironment(
        Environment $environment,
        string $clientId,
        string $subject,
        ?string $organizationId = null,
    ): self {
        return new self($environment->id, $clientId, $subject, $organizationId);
    }

    /** Returns a non-reversible key suitable for a store or lock namespace. */
    public function storageKey(): string
    {
        return hash('sha256', implode("\0", [
            $this->environmentId,
            $this->clientId,
            $this->subject,
            $this->organizationId ?? '',
        ]));
    }
}
