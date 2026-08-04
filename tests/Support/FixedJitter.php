<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use DevLancer\VonHalsky\Internal\Http\JitterInterface;

final class FixedJitter implements JitterInterface
{
    public function __construct(private readonly float $factor = 1.0)
    {
    }

    public function apply(float $maximumSeconds): float
    {
        return $maximumSeconds * $this->factor;
    }
}
