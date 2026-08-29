<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

final class NumericFloatValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::NUMERIC_FLOAT;
    }

    public function isValid(string $value): bool
    {
        return preg_match('/\A(?:\d+(?:\.\d+)?|\.\d+)\z/D', $value) === 1;
    }
}
