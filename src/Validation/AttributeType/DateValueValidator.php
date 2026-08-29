<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class DateValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::DATE;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $context->value);
        $errors = \DateTimeImmutable::getLastErrors();

        $valid = $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $context->value;
        if ($valid) {
            return AttributeValueTypeValidationResult::valid();
        }

        return new AttributeValueTypeValidationResult([
            new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must be a valid calendar date in YYYY-MM-DD format.',
            ),
        ]);
    }
}
