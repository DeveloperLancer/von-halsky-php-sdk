<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

final class CliResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
    ) {
    }
}
