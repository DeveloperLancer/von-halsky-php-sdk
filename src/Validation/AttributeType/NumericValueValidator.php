<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class NumericValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::NUMERIC;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        if (preg_match('/\A\d+\z/D', $context->value) === 1) {
            return AttributeValueTypeValidationResult::valid();
        }

        return new AttributeValueTypeValidationResult([
            new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must contain digits only, without a sign.',
            ),
        ]);
    }
}
