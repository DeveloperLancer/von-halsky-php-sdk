<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

/** Serializes token refresh for a context. Implementations must release locks on failure. */
interface LockInterface
{
    /**
     * @template T
     * @param callable(): T $criticalSection
     * @return T
     */
    public function synchronized(TokenContext $context, callable $criticalSection): mixed;
}
