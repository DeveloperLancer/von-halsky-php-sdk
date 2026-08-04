<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use DevLancer\VonHalsky\Internal\Http\SleeperInterface;

final class AdvancingSleeper implements SleeperInterface
{
    /** @var list<float> */
    public array $delays = [];

    public function __construct(private readonly FrozenClock $clock)
    {
    }

    public function sleep(float $seconds): void
    {
        $this->delays[] = $seconds;
        $microseconds = (int) round($seconds * 1_000_000);
        $this->clock->set($this->clock->now()->modify(sprintf('+%d microseconds', $microseconds)));
    }
}
