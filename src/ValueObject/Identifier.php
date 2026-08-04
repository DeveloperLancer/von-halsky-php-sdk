<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use Stringable;

/** Base type for opaque API identifiers. */
abstract class Identifier implements Stringable
{
    final protected function __construct(public readonly string $value)
    {
        if ($value === '' || strlen($value) > 255 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidRequestException(static::class, 'must contain 1 to 255 printable bytes');
        }
    }

    /** @return static */
    final public static function fromString(string $value): self
    {
        return new static($value);
    }

    final public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
