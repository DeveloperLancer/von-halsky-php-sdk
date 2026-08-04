<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal;

use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeDictionary;
use DevLancer\VonHalsky\Model\Category\AttributeDictionaryOption;
use DevLancer\VonHalsky\Model\Category\AttributeExpectedValue;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Model\Category\CategoryRelation;
use DevLancer\VonHalsky\Model\Organization\Organization;
use DevLancer\VonHalsky\Model\Organization\OrganizationParent;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** @internal Hydrates the phase 5 domain responses. */
final class DomainResponseHydrator
{
    /**
     * @param list<mixed> $data
     * @return list<Organization>
     */
    public static function organizations(array $data): array
    {
        $result = [];
        foreach ($data as $index => $item) {
            $path = sprintf('$[%d]', $index);
            $item = self::object($item, $path);
            $parent = self::nullableObject($item, 'parent', $path);
            $result[] = new Organization(
                self::nullableIdentifier($item, 'id', $path, OrganizationId::class),
                self::nullableString($item, 'name', $path),
                self::nullableString($item, 'status', $path),
                self::nullableString($item, 'type', $path),
                self::nullableString($item, 'logoUrl', $path),
                self::nullableString($item, 'operationalRegion', $path),
                $parent === null ? null : new OrganizationParent(
                    self::nullableIdentifier($parent, 'id', $path . '.parent', OrganizationId::class),
                    self::nullableString($parent, 'name', $path . '.parent'),
                    self::nullableString($parent, 'status', $path . '.parent'),
                    ResponseHydrator::additionalData($parent, ['id', 'name', 'status']),
                ),
                ResponseHydrator::additionalData(
                    $item,
                    ['id', 'name', 'status', 'type', 'logoUrl', 'operationalRegion', 'parent'],
                ),
            );
        }

        return $result;
    }

    /**
     * @param list<mixed> $data
     * @return list<Category>
     */
    public static function categories(array $data): array
    {
        $result = [];
        foreach ($data as $index => $item) {
            $result[] = self::category(self::object($item, sprintf('$[%d]', $index)), sprintf('$[%d]', $index));
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    public static function category(array $data, string $path = '$'): Category
    {
        $children = [];
        foreach (self::optionalList($data, 'children', $path) as $index => $child) {
            $childPath = sprintf('%s.children[%d]', $path, $index);
            $children[] = self::category(self::object($child, $childPath), $childPath);
        }

        $relations = [];
        foreach (self::optionalList($data, 'relations', $path) as $index => $relation) {
            $relationPath = sprintf('%s.relations[%d]', $path, $index);
            $relation = self::object($relation, $relationPath);
            $relations[] = new CategoryRelation(
                self::nullableIdentifier($relation, 'id', $relationPath, CategoryId::class),
                self::nullableString($relation, 'relation', $relationPath),
                ResponseHydrator::additionalData($relation, ['id', 'relation']),
            );
        }

        return new Category(
            CategoryId::fromString(ResponseHydrator::string($data, 'id', $path)),
            ResponseHydrator::string($data, 'name', $path),
            self::boolean($data, 'leaf', $path),
            self::boolean($data, 'doesNotRequireGpsrInfo', $path),
            self::nullableString($data, 'description', $path),
            $children,
            $relations,
            self::stringMap($data, 'metadata', $path),
            ResponseHydrator::additionalData(
                $data,
                ['id', 'name', 'leaf', 'doesNotRequireGpsrInfo', 'description', 'children', 'relations', 'metadata'],
            ),
        );
    }

    /**
     * @param list<mixed> $data
     * @return list<AttributeDefinition>
     */
    public static function attributes(array $data): array
    {
        $result = [];
        foreach ($data as $index => $item) {
            $path = sprintf('$[%d]', $index);
            $item = self::object($item, $path);
            $dictionary = self::nullableObject($item, 'dictionary', $path);
            $result[] = new AttributeDefinition(
                ResponseHydrator::string($item, 'id', $path),
                ResponseHydrator::string($item, 'name', $path),
                AttributeType::fromString(ResponseHydrator::string($item, 'type', $path)),
                AttributeExpectedValue::fromString(ResponseHydrator::string($item, 'expectedValue', $path)),
                self::nullableString($item, 'description', $path),
                self::nullableString($item, 'lang', $path),
                $dictionary === null ? null : self::dictionary($dictionary, $path . '.dictionary'),
                ResponseHydrator::additionalData(
                    $item,
                    ['id', 'name', 'type', 'expectedValue', 'description', 'lang', 'dictionary'],
                ),
            );
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    private static function dictionary(array $data, string $path): AttributeDictionary
    {
        $options = [];
        foreach (ResponseHydrator::list($data, 'options', $path) as $index => $option) {
            $optionPath = sprintf('%s.options[%d]', $path, $index);
            $option = self::object($option, $optionPath);
            $options[] = new AttributeDictionaryOption(
                ResponseHydrator::string($option, 'id', $optionPath),
                ResponseHydrator::string($option, 'value', $optionPath),
                self::boolean($option, 'active', $optionPath),
                self::nullableString($option, 'lang', $optionPath),
                ResponseHydrator::additionalData($option, ['id', 'value', 'active', 'lang']),
            );
        }

        return new AttributeDictionary(
            ResponseHydrator::string($data, 'id', $path),
            ResponseHydrator::string($data, 'name', $path),
            $options,
            ResponseHydrator::additionalData($data, ['id', 'name', 'options']),
        );
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $path): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new ResponseMappingException($path, 'must be a JSON object');
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new ResponseMappingException($path, 'must use string keys');
            }
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private static function nullableObject(array $data, string $field, string $path): ?array
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }

        return self::object($data[$field], $path . '.' . $field);
    }

    /** @param array<string, mixed> $data */
    private static function nullableString(array $data, string $field, string $path): ?string
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }
        if (!is_string($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'must be a string or null');
        }

        return $data[$field];
    }

    /**
     * @template T of OrganizationId|CategoryId
     * @param array<string, mixed> $data
     * @param class-string<T>      $class
     * @return T|null
     */
    private static function nullableIdentifier(array $data, string $field, string $path, string $class): ?object
    {
        $value = self::nullableString($data, $field, $path);

        return $value === null ? null : $class::fromString($value);
    }

    /** @param array<string, mixed> $data */
    private static function boolean(array $data, string $field, string $path): bool
    {
        if (!array_key_exists($field, $data) || !is_bool($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'required boolean is missing or invalid');
        }

        return $data[$field];
    }

    /**
     * @param array<string, mixed> $data
     * @return list<mixed>
     */
    private static function optionalList(array $data, string $field, string $path): array
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return [];
        }
        if (!is_array($data[$field]) || !array_is_list($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'must be an array or null');
        }

        return $data[$field];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private static function stringMap(array $data, string $field, string $path): array
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return [];
        }
        $map = self::object($data[$field], $path . '.' . $field);
        foreach ($map as $key => $value) {
            if (!is_string($value)) {
                throw new ResponseMappingException($path . '.' . $field . '.' . $key, 'must be a string');
            }
        }

        /** @var array<string, string> $map */
        return $map;
    }
}
