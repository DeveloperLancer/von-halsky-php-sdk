<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

/** Dictionary membership is validated separately against category dictionary options. */
final class DictionaryValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::DICTIONARY;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        return AttributeValueTypeValidationResult::valid();
    }
}
