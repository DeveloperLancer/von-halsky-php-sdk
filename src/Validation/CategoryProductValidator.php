<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeExpectedValue;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** Explicitly validates product attributes against definitions for one category. */
final class CategoryProductValidator
{
    /** @var array<string, AttributeDefinition> */
    private readonly array $definitions;

    /** @param list<AttributeDefinition> $attributeDefinitions */
    public function __construct(
        public readonly CategoryId $categoryId,
        array $attributeDefinitions,
    ) {
        $this->definitions = self::indexDefinitions($attributeDefinitions);
    }

    /**
     * @param array<mixed> $attributeDefinitions
     * @return array<string, AttributeDefinition>
     */
    private static function indexDefinitions(array $attributeDefinitions): array
    {
        if (!array_is_list($attributeDefinitions)) {
            throw new \InvalidArgumentException('Category attribute definitions must be a list.');
        }

        $definitions = [];
        foreach ($attributeDefinitions as $definition) {
            if (!$definition instanceof AttributeDefinition) {
                throw new \InvalidArgumentException('Category attribute definitions must contain only AttributeDefinition objects.');
            }
            if ($definition->id === '') {
                throw new \InvalidArgumentException('Category attribute definition ID cannot be empty.');
            }
            if (isset($definitions[$definition->id])) {
                throw new \InvalidArgumentException(sprintf('Category attribute definition "%s" is duplicated.', $definition->id));
            }
            $definitions[$definition->id] = $definition;
        }

        return $definitions;
    }

    public function validate(ProductProposal $product): CategoryProductValidationResult
    {
        if (!$this->categoryId->equals($product->categoryId)) {
            return new CategoryProductValidationResult([
                self::issue(
                    CategoryProductValidationIssue::CATEGORY_MISMATCH,
                    CategoryProductValidationIssue::ERROR,
                    sprintf(
                        'Product category "%s" does not match validator category "%s".',
                        $product->categoryId->value,
                        $this->categoryId->value,
                    ),
                    'Product.categoryId',
                ),
            ]);
        }

        $issues = $this->definitionWarnings();
        /** @var array<string, AttributeValue> $attributes */
        $attributes = [];

        foreach ($product->attributes as $index => $attribute) {
            $fieldPath = sprintf('Product.attributes[%d]', $index);
            if (isset($attributes[$attribute->id])) {
                $definition = $this->definitions[$attribute->id] ?? null;
                $issues[] = self::issue(
                    CategoryProductValidationIssue::ATTRIBUTE_DUPLICATED,
                    CategoryProductValidationIssue::ERROR,
                    sprintf('Product attribute "%s" is supplied more than once.', $attribute->id),
                    $fieldPath,
                    $attribute->id,
                    $definition?->name,
                );
                continue;
            }
            $attributes[$attribute->id] = $attribute;

            $definition = $this->definitions[$attribute->id] ?? null;
            if ($definition === null) {
                $issues[] = self::issue(
                    CategoryProductValidationIssue::ATTRIBUTE_UNKNOWN,
                    CategoryProductValidationIssue::ERROR,
                    sprintf('Product attribute "%s" is not defined for category "%s".', $attribute->id, $this->categoryId->value),
                    $fieldPath,
                    $attribute->id,
                );
                continue;
            }

            $this->validateCardinality($definition, $attribute, $fieldPath, $issues);
            $this->validateDictionary($definition, $attribute, $fieldPath, $issues);
        }

        foreach ($this->definitions as $definition) {
            if (isset($attributes[$definition->id])) {
                continue;
            }
            if (in_array($definition->expectedValue->value, [AttributeExpectedValue::ONE, AttributeExpectedValue::AT_LEAST_ONE], true)) {
                $issues[] = self::issue(
                    CategoryProductValidationIssue::REQUIRED_ATTRIBUTE_MISSING,
                    CategoryProductValidationIssue::ERROR,
                    sprintf('Required category attribute "%s" is missing.', $definition->name),
                    'Product.attributes',
                    $definition->id,
                    $definition->name,
                );
            }
        }

        return new CategoryProductValidationResult($issues);
    }

    /** @return list<CategoryProductValidationIssue> */
    private function definitionWarnings(): array
    {
        $issues = [];
        foreach ($this->definitions as $definition) {
            if (!$definition->expectedValue->isKnown()) {
                $issues[] = self::issue(
                    CategoryProductValidationIssue::EXPECTED_VALUE_UNSUPPORTED,
                    CategoryProductValidationIssue::WARNING,
                    sprintf('Attribute "%s" uses unsupported expected value "%s".', $definition->name, $definition->expectedValue->value),
                    'Product.attributes',
                    $definition->id,
                    $definition->name,
                );
            }
            if (!$definition->type->isKnown()) {
                $issues[] = self::issue(
                    CategoryProductValidationIssue::ATTRIBUTE_TYPE_UNSUPPORTED,
                    CategoryProductValidationIssue::WARNING,
                    sprintf('Attribute "%s" uses unsupported type "%s".', $definition->name, $definition->type->value),
                    'Product.attributes',
                    $definition->id,
                    $definition->name,
                );
            }
            if ($definition->type->value === AttributeType::DICTIONARY && $definition->dictionary === null) {
                $issues[] = self::issue(
                    CategoryProductValidationIssue::DICTIONARY_MISSING,
                    CategoryProductValidationIssue::WARNING,
                    sprintf('Dictionary attribute "%s" has no dictionary definition.', $definition->name),
                    'Product.attributes',
                    $definition->id,
                    $definition->name,
                );
            }
        }

        return $issues;
    }

    /** @param list<CategoryProductValidationIssue> $issues */
    private function validateCardinality(
        AttributeDefinition $definition,
        AttributeValue $attribute,
        string $fieldPath,
        array &$issues,
    ): void {
        $count = count($attribute->values);
        $valid = match ($definition->expectedValue->value) {
            AttributeExpectedValue::NULL_OR_ONE, AttributeExpectedValue::ONE => $count === 1,
            AttributeExpectedValue::AT_LEAST_ONE, AttributeExpectedValue::ANY => $count >= 1,
            default => true,
        };

        if (!$valid) {
            $issues[] = self::issue(
                CategoryProductValidationIssue::ATTRIBUTE_CARDINALITY_INVALID,
                CategoryProductValidationIssue::ERROR,
                sprintf(
                    'Attribute "%s" has %d values, which does not satisfy "%s".',
                    $definition->name,
                    $count,
                    $definition->expectedValue->value,
                ),
                $fieldPath . '.values',
                $definition->id,
                $definition->name,
            );
        }
    }

    /** @param list<CategoryProductValidationIssue> $issues */
    private function validateDictionary(
        AttributeDefinition $definition,
        AttributeValue $attribute,
        string $fieldPath,
        array &$issues,
    ): void {
        if ($definition->dictionary === null) {
            return;
        }

        $active = [];
        $inactive = [];
        foreach ($definition->dictionary->options as $option) {
            if ($option->active) {
                $active[$option->value] = true;
            } else {
                $inactive[$option->value] = true;
            }
        }

        foreach ($attribute->values as $index => $value) {
            if (isset($active[$value])) {
                continue;
            }
            $inactiveValue = isset($inactive[$value]);
            $issues[] = self::issue(
                $inactiveValue
                    ? CategoryProductValidationIssue::DICTIONARY_VALUE_INACTIVE
                    : CategoryProductValidationIssue::DICTIONARY_VALUE_UNKNOWN,
                CategoryProductValidationIssue::ERROR,
                $inactiveValue
                    ? sprintf('Dictionary value "%s" for attribute "%s" is inactive.', $value, $definition->name)
                    : sprintf('Dictionary value "%s" is not defined for attribute "%s".', $value, $definition->name),
                sprintf('%s.values[%d]', $fieldPath, $index),
                $definition->id,
                $definition->name,
            );
        }
    }

    private static function issue(
        string $code,
        string $level,
        string $message,
        string $fieldPath,
        ?string $attributeId = null,
        ?string $attributeName = null,
    ): CategoryProductValidationIssue {
        return new CategoryProductValidationIssue($code, $level, $message, $fieldPath, $attributeId, $attributeName);
    }
}
