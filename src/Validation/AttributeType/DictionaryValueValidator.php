<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

/** Dictionary membership is validated separately against category dictionary options. */
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
        $issue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::DICTIONARY);

        return new AttributeValueTypeValidationResult($issue === null ? [] : [$issue]);
    }
}
