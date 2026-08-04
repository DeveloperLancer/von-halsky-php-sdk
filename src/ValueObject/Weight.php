<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Validation\RequestValidator;

/** Package weight in grams. */
final class Weight
{
    public function __construct(public readonly int $grams)
    {
        RequestValidator::integerRange($grams, 1, 1000000, 'Dimension.weight');
    }
}
