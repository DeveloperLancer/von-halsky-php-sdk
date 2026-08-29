<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class DateValueValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::DATE;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::DATE);
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $context->value);
        $errors = \DateTimeImmutable::getLastErrors();

        $valid = $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $context->value;
        if (!$valid) {
            $issues[] = new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must be a valid calendar date in YYYY-MM-DD format.',
            );
        }

        return new AttributeValueTypeValidationResult($issues);
    }
}
