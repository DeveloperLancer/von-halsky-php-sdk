<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

/** A validated monetary amount represented without binary floating point. */
final class Money
{
    private function __construct(
        public readonly string $amount,
        public readonly Currency $currency,
    ) {
    }

    public static function fromDecimal(string $amount, Currency $currency = Currency::PLN): self
    {
        if (preg_match('/^(?<whole>0|[1-9][0-9]{0,5})(?:\.(?<fraction>[0-9]{1,2}))?$/D', $amount, $matches) !== 1) {
            throw new InvalidRequestException('Money.amount', 'must be a decimal string with at most two fractional digits');
        }

        $normalized = $matches['whole'] . '.' . str_pad($matches['fraction'] ?? '', 2, '0');
        $minorUnits = ((int) $matches['whole'] * 100) + (int) str_pad($matches['fraction'] ?? '', 2, '0');
        if ($minorUnits < 1 || $minorUnits > 99999999) {
            throw new InvalidRequestException('Money.amount', 'must be between 0.01 and 999999.99');
        }

        return new self($normalized, $currency);
    }

    public function minorUnits(): int
    {
        return ((int) substr($this->amount, 0, -3) * 100) + (int) substr($this->amount, -2);
    }
}
