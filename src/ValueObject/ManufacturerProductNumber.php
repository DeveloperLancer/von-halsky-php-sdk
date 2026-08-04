<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Validation\RequestValidator;
use Stringable;

/** Manufacturer-assigned product identifier. */
final class ManufacturerProductNumber implements Stringable
{
    public function __construct(public readonly string $value)
    {
        RequestValidator::stringLength($value, 1, 255, 'ManufacturerProductNumber');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
