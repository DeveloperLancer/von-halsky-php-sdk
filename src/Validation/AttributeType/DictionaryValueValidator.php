<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

/** Validates length and exact membership in the category dictionary's localized option values. */
final class DictionaryValueValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::DICTIONARY;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::DICTIONARY);
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }

        $dictionary = $context->definition->dictionary;
        if ($dictionary === null) {
            return new AttributeValueTypeValidationResult($issues);
        }

        $matchingInactiveOption = false;
        foreach ($dictionary->options as $option) {
            if ($option->value !== $context->value) {
                continue;
            }

            if ($option->active) {
                return new AttributeValueTypeValidationResult($issues);
            }

            $matchingInactiveOption = true;
        }

        if ($matchingInactiveOption) {
            $issues[] = new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::DICTIONARY_VALUE_INACTIVE,
                AttributeValueTypeValidationIssue::ERROR,
                sprintf(
                    'Dictionary value "%s" for attribute "%s" is inactive.',
                    $context->value,
                    $context->definition->name,
                ),
            );

            return new AttributeValueTypeValidationResult($issues);
        }

        $issues[] = new AttributeValueTypeValidationIssue(
            CategoryProductValidationIssue::DICTIONARY_VALUE_UNKNOWN,
            AttributeValueTypeValidationIssue::ERROR,
            sprintf(
                'Dictionary value "%s" is not defined for attribute "%s".',
                $context->value,
                $context->definition->name,
            ),
        );

        return new AttributeValueTypeValidationResult($issues);
    }
}
