<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DevLancer\VonHalsky\Exception\InvalidRequestException;

/** An immutable instant normalized to UTC for unambiguous request serialization. */
final class UtcDateTime
{
    private function __construct(public readonly DateTimeImmutable $value)
    {
    }

    public static function from(DateTimeInterface $value): self
    {
        return new self(DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC')));
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]+)?(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $value) !== 1) {
            throw new InvalidRequestException('dateTime', 'must be an ISO-8601 date with an explicit offset');
        }
        try {
            $parsed = new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new InvalidRequestException('dateTime', 'must be an ISO-8601 date with an explicit offset');
        }

        return self::from($parsed);
    }

    public function toAtomString(): string
    {
        return $this->value->format('Y-m-d\TH:i:sP');
    }
}
