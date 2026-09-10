<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\ReturnOrder;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible return status returned by the API. */
final class ReturnStatus extends ExtensibleEnum
{
    public const NEW = 'NEW';
    public const ACCEPTED = 'ACCEPTED';
    public const REJECTED = 'REJECTED';

    protected static function knownValues(): array
    {
        return [self::NEW, self::ACCEPTED, self::REJECTED];
    }
}
