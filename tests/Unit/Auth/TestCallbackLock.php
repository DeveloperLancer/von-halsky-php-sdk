<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Auth;

use Closure;
use DevLancer\VonHalsky\Auth\LockInterface;
use DevLancer\VonHalsky\Auth\TokenContext;

/** @internal */
final class TestCallbackLock implements LockInterface
{
    /** @param Closure(): void $beforeCriticalSection */
    public function __construct(private readonly Closure $beforeCriticalSection)
    {
    }

    public function synchronized(TokenContext $context, callable $criticalSection): mixed
    {
        ($this->beforeCriticalSection)();

        return $criticalSection();
    }
}
