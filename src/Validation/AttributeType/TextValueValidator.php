<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

/** Validates TEXT_VALUE; the 1024-character limit is confirmed on Stage. */
final class TextValueValidator implements AttributeValueTypeValidatorInterface
{
    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::TEXT_VALUE;
    }

    public function isValid(string $value): bool
    {
        $characterCount = preg_match_all('/./us', $value);

        return $characterCount !== false && $characterCount <= self::MAX_LENGTH;
    }
}
