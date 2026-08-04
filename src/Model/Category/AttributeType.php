<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible category attribute value type. */
final class AttributeType extends ExtensibleEnum
{
    public const TEXT_VALUE = 'TEXT_VALUE';
    public const LONG_TEXT_VALUE = 'LONG_TEXT_VALUE';
    public const DICTIONARY = 'DICTIONARY';
    public const NUMERIC = 'NUMERIC';
    public const NUMERIC_FLOAT = 'NUMERIC_FLOAT';
    public const DATE = 'DATE';
    public const URL = 'URL';

    protected static function knownValues(): array
    {
        return [
            self::TEXT_VALUE,
            self::LONG_TEXT_VALUE,
            self::DICTIONARY,
            self::NUMERIC,
            self::NUMERIC_FLOAT,
            self::DATE,
            self::URL,
        ];
    }
}
