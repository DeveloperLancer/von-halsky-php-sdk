<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\ResponseMappingException;

/** @internal Strict helpers used by generated and hand-written response hydrators. */
final class ResponseHydrator
{
    /** @param array<string, mixed> $data */
    public static function string(array $data, string $field, string $path = '$'): string
    {
        if (!array_key_exists($field, $data) || !is_string($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'required string is missing or invalid');
        }

        return $data[$field];
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $field, string $path = '$'): ?string
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }
        if (!is_string($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'must be a string or null');
        }

        return $data[$field];
    }

    /** @param array<string, mixed> $data */
    public static function integer(array $data, string $field, string $path = '$'): int
    {
        if (!array_key_exists($field, $data) || !is_int($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'required integer is missing or invalid');
        }

        return $data[$field];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<mixed>
     */
    public static function list(array $data, string $field, string $path = '$'): array
    {
        if (!array_key_exists($field, $data) || !is_array($data[$field]) || !array_is_list($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'required JSON array is missing or invalid');
        }

        return $data[$field];
    }

    /** @param array<string, mixed> $data */
    public static function dateTime(array $data, string $field, string $path = '$'): DateTimeImmutable
    {
        $value = self::string($data, $field, $path);
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]+)?(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $value) !== 1) {
            throw new ResponseMappingException($path . '.' . $field, 'must be an ISO-8601 date-time with an explicit offset');
        }
        try {
            $date = new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ResponseMappingException($path . '.' . $field, 'must be a valid date-time');
        }

        return $date;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $known
     *
     * @return array<string, mixed>
     */
    public static function additionalData(array $data, array $known): array
    {
        return array_diff_key($data, array_fill_keys($known, true));
    }
}
