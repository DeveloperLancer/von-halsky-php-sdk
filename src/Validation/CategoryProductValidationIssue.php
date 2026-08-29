<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

/** One category-specific product validation error or warning. */
final class CategoryProductValidationIssue
{
    public const ERROR = 'error';
    public const WARNING = 'warning';

    public const CATEGORY_MISMATCH = 'category_mismatch';
    public const REQUIRED_ATTRIBUTE_MISSING = 'required_attribute_missing';
    public const ATTRIBUTE_CARDINALITY_INVALID = 'attribute_cardinality_invalid';
    public const ATTRIBUTE_DUPLICATED = 'attribute_duplicated';
    public const ATTRIBUTE_UNKNOWN = 'attribute_unknown';
    public const ATTRIBUTE_TYPE_INVALID = 'attribute_type_invalid';
    public const ATTRIBUTE_TYPE_VALIDATOR_MISSING = 'attribute_type_validator_missing';
    public const DICTIONARY_VALUE_UNKNOWN = 'dictionary_value_unknown';
    public const DICTIONARY_VALUE_INACTIVE = 'dictionary_value_inactive';
    public const DICTIONARY_MISSING = 'dictionary_missing';
    public const ATTRIBUTE_TYPE_UNSUPPORTED = 'attribute_type_unsupported';
    public const EXPECTED_VALUE_UNSUPPORTED = 'expected_value_unsupported';

    public function __construct(
        public readonly string $code,
        public readonly string $level,
        public readonly string $message,
        public readonly string $fieldPath,
        public readonly ?string $attributeId = null,
        public readonly ?string $attributeName = null,
    ) {
        if (!in_array($level, [self::ERROR, self::WARNING], true)) {
            throw new \InvalidArgumentException('Validation issue level must be "error" or "warning".');
        }
        if ($code === '' || $message === '' || $fieldPath === '') {
            throw new \InvalidArgumentException('Validation issue code, message, and field path cannot be empty.');
        }
    }

    public function isError(): bool
    {
        return $this->level === self::ERROR;
    }
}
