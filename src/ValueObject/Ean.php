<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use Stringable;

/** EAN/GTIN code accepted by the API. */
final class Ean implements Stringable
{
    public function __construct(public readonly string $value)
    {
        if (preg_match('/^[0-9]{1,14}$/D', $value) !== 1) {
            throw new InvalidRequestException('Ean', 'must contain 1 to 14 digits');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
