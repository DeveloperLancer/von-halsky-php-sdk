<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DevLancer\VonHalsky\Exception\AuthenticationFlowException;

/** Immutable RFC 7636 verifier and its S256 challenge. */
final class PkcePair
{
    private function __construct(
        public readonly string $verifier,
        public readonly string $challenge,
    ) {
    }

    public static function generate(): self
    {
        return self::fromVerifier(self::base64UrlEncode(random_bytes(64)));
    }

    /** Creates a pair from a verifier, including RFC 7636 test vectors. */
    public static function fromVerifier(string $verifier): self
    {
        if (preg_match('/\A[A-Za-z0-9._~-]{43,128}\z/D', $verifier) !== 1) {
            throw new AuthenticationFlowException('The PKCE verifier must satisfy RFC 7636 length and character rules.');
        }

        return new self($verifier, self::base64UrlEncode(hash('sha256', $verifier, true)));
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
