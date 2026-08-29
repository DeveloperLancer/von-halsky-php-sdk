<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

/** Shared mechanics for independently configured attribute-type length limits. */
trait ValidatesAttributeValueLength
{
    private function maximumLengthIssue(
        AttributeValueValidationContext $context,
        int $maximumLength,
        string $type,
    ): ?AttributeValueTypeValidationIssue {
        $characterCount = preg_match_all('/./us', $context->value);
        if ($characterCount === false) {
            throw new \LogicException('AttributeValueValidationContext must contain valid UTF-8.');
        }
        if ($characterCount <= $maximumLength) {
            return null;
        }

        return new AttributeValueTypeValidationIssue(
            CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG,
            AttributeValueTypeValidationIssue::ERROR,
            sprintf('%s value must contain at most %d characters.', $type, $maximumLength),
        );
    }
}
