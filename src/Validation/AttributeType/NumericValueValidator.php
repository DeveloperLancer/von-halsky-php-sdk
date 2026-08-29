<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class NumericValueValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::NUMERIC;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::NUMERIC);
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }
        if (preg_match('/\A\d+\z/D', $context->value) !== 1) {
            $issues[] = new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must contain digits only, without a sign.',
            );
        }

        return new AttributeValueTypeValidationResult($issues);
    }
}
