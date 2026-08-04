<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use DevLancer\VonHalsky\Auth\LockInterface;
use DevLancer\VonHalsky\Auth\TokenContext;

final class TestLock implements LockInterface
{
    public function synchronized(TokenContext $context, callable $criticalSection): mixed
    {
        return $criticalSection();
    }
}
