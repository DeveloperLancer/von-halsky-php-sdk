<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

/** TEXT_VALUE has no additional type rule; the registry enforces the common AttributeValueItem limit. */
final class TextValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::TEXT_VALUE;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        return AttributeValueTypeValidationResult::valid();
    }
}
