<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Serialization;

use BackedEnum;
use DateTimeInterface;
use DevLancer\VonHalsky\Exception\SerializationException;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\Dimensions;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Identifier;
use DevLancer\VonHalsky\ValueObject\ManufacturerProductNumber;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;
use DevLancer\VonHalsky\ValueObject\Weight;
use JsonSerializable;

/** Converts typed request models to JSON-compatible values at the HTTP boundary. */
final class RequestNormalizer
{
    /** @return array<string, mixed> */
    public function normalize(RequestDtoInterface $request): array
    {
        $normalized = $this->normalizeValue($request->jsonSerialize(), '$');
        if (!is_array($normalized) || array_is_list($normalized)) {
            throw new SerializationException('A request DTO must normalize to a JSON object.');
        }
        /** @var array<string, mixed> $normalized */

        return $normalized;
    }

    private function normalizeValue(mixed $value, string $path): mixed
    {
        if ($value instanceof OptionalValue) {
            if (!$value->isDefined()) {
                throw new SerializationException(sprintf('Undefined optional value at "%s" must be an object field.', $path));
            }

            return $this->normalizeValue($value->value(), $path);
        }
        if ($value instanceof Money) {
            return [
                'amount' => $value->minorUnits() / 100.0,
                'currency' => $value->currency->value,
            ];
        }
        if ($value instanceof Identifier || $value instanceof Ean || $value instanceof ManufacturerProductNumber) {
            return (string) $value;
        }
        if ($value instanceof UtcDateTime) {
            return $value->toAtomString();
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof Dimensions) {
            return ['width' => $value->width, 'height' => $value->height, 'length' => $value->length];
        }
        if ($value instanceof Weight) {
            return $value->grams;
        }
        if ($value instanceof Address) {
            return $this->normalizeObject([
                'street' => $value->street,
                'city' => $value->city,
                'postalCode' => $value->postalCode,
                'countryCode' => (string) $value->countryCode,
                'building' => $value->building === null ? OptionalValue::undefined() : OptionalValue::of($value->building),
                'flat' => $value->flat === null ? OptionalValue::undefined() : OptionalValue::of($value->flat),
                'state' => $value->state === null ? OptionalValue::undefined() : OptionalValue::of($value->state),
            ], $path);
        }
        if ($value instanceof JsonSerializable) {
            return $this->normalizeValue($value->jsonSerialize(), $path);
        }
        if (is_array($value)) {
            if (array_is_list($value)) {
                return $this->normalizeList($value, $path);
            }
            /** @var array<string, mixed> $value */

            return $this->normalizeObject($value, $path);
        }
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        throw new SerializationException(sprintf('Unsupported request value at "%s".', $path));
    }

    /**
     * @param list<mixed> $values
     *
     * @return list<mixed>
     */
    private function normalizeList(array $values, string $path): array
    {
        $result = [];
        foreach ($values as $index => $value) {
            $result[] = $this->normalizeValue($value, sprintf('%s[%d]', $path, $index));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function normalizeObject(array $values, string $path): array
    {
        $result = [];
        foreach ($values as $name => $value) {
            if ($value instanceof OptionalValue && !$value->isDefined()) {
                continue;
            }
            $result[$name] = $this->normalizeValue($value, $path . '.' . $name);
        }

        return $result;
    }
}
