<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Auth;

use DevLancer\VonHalsky\Auth\PkcePair;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;
use PHPUnit\Framework\TestCase;

final class PkcePairTest extends TestCase
{
    public function testMatchesRfc7636S256Vector(): void
    {
        $pair = PkcePair::fromVerifier('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk');

        self::assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', $pair->challenge);
    }

    public function testGeneratesIndependentValidPairs(): void
    {
        $first = PkcePair::generate();
        $second = PkcePair::generate();

        self::assertMatchesRegularExpression('/\A[A-Za-z0-9._~-]{43,128}\z/D', $first->verifier);
        self::assertMatchesRegularExpression('/\A[A-Za-z0-9_-]{43}\z/D', $first->challenge);
        self::assertNotSame($first->verifier, $second->verifier);
        self::assertNotSame($first->challenge, $second->challenge);
    }

    public function testRejectsVerifierOutsideRfcRules(): void
    {
        $this->expectException(AuthenticationFlowException::class);

        PkcePair::fromVerifier('too-short');
    }
}
