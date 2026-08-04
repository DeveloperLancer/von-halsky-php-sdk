<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\ClockInterface;

final class FrozenClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $time)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }

    public function set(DateTimeImmutable $time): void
    {
        $this->time = $time;
    }
}
