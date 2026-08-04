<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal\Http;

/** @internal */
interface SleeperInterface
{
    public function sleep(float $seconds): void;
}
