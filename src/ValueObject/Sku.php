<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Validation\RequestValidator;
use JsonSerializable;
use Stringable;

/** Seller-defined stock keeping unit. */
final class Sku implements JsonSerializable, Stringable
{
    public function __construct(public readonly string $value)
    {
        RequestValidator::stringLength($value, 1, 100, 'Product.sku');
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
