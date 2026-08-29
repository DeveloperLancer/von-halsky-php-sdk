<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

/** Shared request-validation rules. */
final class RequestValidator
{
    public const ATTRIBUTE_VALUE_MAX_LENGTH = 1024;

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
    public static function attributeValues(array $values, string $fieldPath, ?int $maximumLength = null): void
    {
        if (!array_is_list($values)) {
            throw new InvalidRequestException($fieldPath, 'must be a list');
        }

        foreach ($values as $index => $value) {
            if (!is_string($value)) {
                throw new InvalidRequestException(sprintf('%s[%d]', $fieldPath, $index), 'must be a string');
            }
            $characterCount = preg_match_all('/./us', $value);
            if ($characterCount === false) {
                throw new InvalidRequestException(sprintf('%s[%d]', $fieldPath, $index), 'must contain valid UTF-8');
            }
            if ($maximumLength !== null && $characterCount > $maximumLength) {
                throw new InvalidRequestException(sprintf('%s[%d]', $fieldPath, $index), sprintf('must contain at most %d characters', $maximumLength));
            }
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
        $count = count($images);
        if ($count < 1 || $count > 20) {
            throw new InvalidRequestException($fieldPath, 'must contain between 1 and 20 items');
        }
    }

    public static function offerImageFileName(string $fileName, string $fieldPath = 'Offer.images.fileName'): void
    {
        if (preg_match('/\.(?:jpg|png|webp)\z/iD', $fileName) !== 1) {
            throw new InvalidRequestException($fieldPath, 'must use a .jpg, .png, or .webp extension');
        }
    }

    /** @param array<mixed> $manuals */
    public static function gpsrManuals(array $manuals, string $fieldPath = 'GpsrInfo.manuals'): void
    {
        self::itemLimit($manuals, 20, $fieldPath);
    }

    /** @param array<mixed> $attributes */
    public static function productAttributes(array $attributes, string $fieldPath = 'ProductInfo.attributes'): void
    {
        self::itemLimit($attributes, 120, $fieldPath);
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
