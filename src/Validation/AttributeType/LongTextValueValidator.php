<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

/** LONG_TEXT_VALUE has no confirmed server-side length constraint yet. */
final class LongTextValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::LONG_TEXT_VALUE;
    }

    public function isValid(string $value): bool
    {
        return true;
    }
}
