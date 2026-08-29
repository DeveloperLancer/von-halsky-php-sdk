<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

final class NumericValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::NUMERIC;
    }

    public function isValid(string $value): bool
    {
        return preg_match('/\A\d+\z/D', $value) === 1;
    }
}
