<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

use DateTimeImmutable;
use DateTimeZone;

/** Uses the system UTC clock. */
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
