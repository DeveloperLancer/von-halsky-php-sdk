<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Reliability;

use DevLancer\VonHalsky\Exception\ConfigurationException;

/** Bounded retry policy for safe GET requests only. */
final class RetryPolicy
{
    public function __construct(
        public readonly int $maxAttempts = 2,
        public readonly float $baseDelaySeconds = 0.1,
        public readonly float $maximumDelaySeconds = 0.5,
        public readonly float $maximumElapsedSeconds = 1.0,
    ) {
        if ($maxAttempts < 1) {
            throw new ConfigurationException('Retry maxAttempts must be at least one.');
        }
        foreach ([$baseDelaySeconds, $maximumDelaySeconds, $maximumElapsedSeconds] as $value) {
            if (!is_finite($value) || $value < 0.0) {
                throw new ConfigurationException('Retry durations must be finite non-negative numbers.');
            }
        }
        if ($maximumElapsedSeconds === 0.0 && $maxAttempts > 1) {
            throw new ConfigurationException('Retry elapsed-time limit must be positive when retries are enabled.');
        }
    }
}
