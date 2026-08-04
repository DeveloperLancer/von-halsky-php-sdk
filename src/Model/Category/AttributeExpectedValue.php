<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Category;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible cardinality expected for a category attribute. */
final class AttributeExpectedValue extends ExtensibleEnum
{
    public const NULL_OR_ONE = 'NULL_OR_ONE';
    public const ONE = 'ONE';
    public const AT_LEAST_ONE = 'AT_LEAST_ONE';
    public const ANY = 'ANY';

    protected static function knownValues(): array
    {
        return [self::NULL_OR_ONE, self::ONE, self::AT_LEAST_ONE, self::ANY];
    }
}
