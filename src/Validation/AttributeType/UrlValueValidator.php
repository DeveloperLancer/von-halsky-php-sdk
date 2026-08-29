<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;

final class UrlValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::URL;
    }

    public function isValid(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return $scheme === 'http' || $scheme === 'https';
    }
}
