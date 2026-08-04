<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal\Http;

/** @internal */
final class FullJitter implements JitterInterface
{
    public function apply(float $maximumSeconds): float
    {
        if ($maximumSeconds <= 0.0) {
            return 0.0;
        }

        return $maximumSeconds * (random_int(0, 1_000_000) / 1_000_000);
    }
}
