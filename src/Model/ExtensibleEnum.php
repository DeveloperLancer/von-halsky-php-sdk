<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model;

use DevLancer\VonHalsky\Exception\ResponseMappingException;
use Stringable;

/** Forward-compatible string value returned by the API. */
abstract class ExtensibleEnum implements Stringable
{
    final protected function __construct(public readonly string $value)
    {
        if ($value === '') {
            throw new ResponseMappingException(static::class, 'enum value cannot be empty');
        }
    }

    /** @return static */
    final public static function fromString(string $value): self
    {
        return new static($value);
    }

    final public function isKnown(): bool
    {
        return in_array($this->value, static::knownValues(), true);
    }

    final public function knownValue(): ?string
    {
        return $this->isKnown() ? $this->value : null;
    }

    final public function __toString(): string
    {
        return $this->value;
    }

    /** @return list<string> */
    abstract protected static function knownValues(): array;
}
