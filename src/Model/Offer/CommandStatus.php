<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible asynchronous command status. */
final class CommandStatus extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['PENDING', 'SUCCESS', 'FAILURE'];
    }
}
