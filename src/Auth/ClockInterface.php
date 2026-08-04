<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateTimeImmutable;

/** Supplies time to expiry-sensitive authentication components. */
interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
