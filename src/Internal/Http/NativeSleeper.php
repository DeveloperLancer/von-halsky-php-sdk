<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal\Http;

use DevLancer\VonHalsky\Exception\ConfigurationException;

/** @internal */
final class NativeSleeper implements SleeperInterface
{
    public function sleep(float $seconds): void
    {
        if (!is_finite($seconds) || $seconds < 0.0) {
            throw new ConfigurationException('Sleep duration must be a finite non-negative number.');
        }
        if ($seconds > 0.0) {
            usleep((int) min($seconds * 1_000_000, PHP_INT_MAX));
        }
    }
}
