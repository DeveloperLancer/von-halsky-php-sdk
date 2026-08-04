<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Validation\RequestValidator;

/** Package dimensions in millimetres. */
final class Dimensions
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int $length,
    ) {
        RequestValidator::integerRange($width, 1, 10000, 'Dimension.width');
        RequestValidator::integerRange($height, 1, 10000, 'Dimension.height');
        RequestValidator::integerRange($length, 1, 10000, 'Dimension.length');
    }
}
