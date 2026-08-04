<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

/** Persists complete token sets atomically for one token context. */
interface TokenStoreInterface
{
    public function load(TokenContext $context): ?TokenSet;

    public function save(TokenContext $context, TokenSet $tokens): void;
}
