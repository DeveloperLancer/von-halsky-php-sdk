<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model;

use LogicException;

/**
 * A PATCH field that distinguishes omission, explicit null, and a value.
 *
 * @template-covariant T
 */
final class OptionalValue
{
    /** @param T|null $value */
    private function __construct(
        private readonly bool $defined,
        private readonly mixed $value,
    ) {
    }

    /** @return self<never> */
    public static function undefined(): self
    {
        /** @var self<never> $undefined */
        $undefined = new self(false, null);

        return $undefined;
    }

    /** @return self<null> */
    public static function null(): self
    {
        return new self(true, null);
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    public static function of(mixed $value): self
    {
        return new self(true, $value);
    }

    public function isDefined(): bool
    {
        return $this->defined;
    }

    public function isNull(): bool
    {
        return $this->defined && $this->value === null;
    }

    /** @return T|null */
    public function value(): mixed
    {
        if (!$this->defined) {
            throw new LogicException('An undefined OptionalValue has no value.');
        }

        return $this->value;
    }
}
