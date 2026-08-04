<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

/** Shared validation rules confirmed by the supported API 1.6 metadata. */
final class RequestValidator
{
    public static function integerRange(int $value, int $minimum, int $maximum, string $fieldPath): void
    {
        if ($value < $minimum || $value > $maximum) {
            throw new InvalidRequestException($fieldPath, sprintf('must be between %d and %d', $minimum, $maximum));
        }
    }

    public static function stringLength(string $value, int $minimum, int $maximum, string $fieldPath): void
    {
        $result = preg_match_all('/./us', $value, $matches);
        if ($result === false) {
            throw new InvalidRequestException($fieldPath, 'must contain valid UTF-8');
        }
        if ($result < $minimum || $result > $maximum) {
            throw new InvalidRequestException($fieldPath, sprintf('must contain between %d and %d characters', $minimum, $maximum));
        }
    }

    /** @param array<mixed> $values */
    public static function itemLimit(array $values, int $maximum, string $fieldPath): void
    {
        if (count($values) > $maximum) {
            throw new InvalidRequestException($fieldPath, sprintf('must contain at most %d items', $maximum));
        }
    }

    /** @param array<mixed> $images */
    public static function offerImages(array $images, string $fieldPath = 'Offer.images'): void
    {
        self::itemLimit($images, 20, $fieldPath);
    }

    /** @param array<mixed> $manuals */
    public static function gpsrManuals(array $manuals, string $fieldPath = 'GpsrInfo.manuals'): void
    {
        self::itemLimit($manuals, 20, $fieldPath);
    }

    /** @param array<mixed> $attributes */
    public static function productAttributes(array $attributes, string $fieldPath = 'ProductInfo.attributes'): void
    {
        self::itemLimit($attributes, 20, $fieldPath);
    }

    /** @param array<mixed> $positions */
    public static function depositPositions(array $positions, string $fieldPath = 'DepositPositions'): void
    {
        self::itemLimit($positions, 10, $fieldPath);
    }

    /** @param array<mixed> $offers */
    public static function offerBatch(array $offers, string $fieldPath = 'offers'): void
    {
        self::itemLimit($offers, 500, $fieldPath);
    }

    public static function daysToShip(int $days, string $fieldPath = 'ShippingTime.daysToShip'): void
    {
        self::integerRange($days, 0, 60, $fieldPath);
    }
}
