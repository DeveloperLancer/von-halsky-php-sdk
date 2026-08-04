<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use Stringable;

/** Uppercase ISO 3166-1 alpha-2 country code. */
final class CountryCode implements Stringable
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper($value);
        if (preg_match('/^[A-Z]{2}$/D', $normalized) !== 1) {
            throw new InvalidRequestException('Address.countryCode', 'must be a two-letter country code');
        }
        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
