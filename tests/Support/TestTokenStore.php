<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use DevLancer\VonHalsky\Auth\TokenContext;
use DevLancer\VonHalsky\Auth\TokenSet;
use DevLancer\VonHalsky\Auth\TokenStoreInterface;

final class TestTokenStore implements TokenStoreInterface
{
    /** @var array<string, TokenSet> */
    private array $tokens = [];

    public function load(TokenContext $context): ?TokenSet
    {
        return $this->tokens[$context->storageKey()] ?? null;
    }

    public function save(TokenContext $context, TokenSet $tokens): void
    {
        $this->tokens[$context->storageKey()] = $tokens;
    }
}
