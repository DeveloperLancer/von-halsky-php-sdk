<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Validates constraints shared by every AttributeValueItem in the official contract. */
final class AttributeValueItemValidator
{
    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $characterCount = preg_match_all('/./us', $context->value);
        if ($characterCount === false) {
            throw new \LogicException('AttributeValueValidationContext must contain valid UTF-8.');
        }
        if ($characterCount <= RequestValidator::ATTRIBUTE_VALUE_MAX_LENGTH) {
            return AttributeValueTypeValidationResult::valid();
        }

        return new AttributeValueTypeValidationResult([
            new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG,
                AttributeValueTypeValidationIssue::ERROR,
                sprintf('Value must contain at most %d characters.', RequestValidator::ATTRIBUTE_VALUE_MAX_LENGTH),
            ),
        ]);
    }
}
