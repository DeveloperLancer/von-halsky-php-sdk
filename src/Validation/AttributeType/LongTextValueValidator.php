<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

final class LongTextValueValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::LONG_TEXT_VALUE;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::LONG_TEXT_VALUE);

        return new AttributeValueTypeValidationResult($issue === null ? [] : [$issue]);
    }
}
