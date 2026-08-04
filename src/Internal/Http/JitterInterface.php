<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal\Http;

/** @internal */
interface JitterInterface
{
    public function apply(float $maximumSeconds): float;
}
