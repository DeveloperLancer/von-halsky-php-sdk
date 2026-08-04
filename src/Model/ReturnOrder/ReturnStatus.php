<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\ReturnOrder;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

final class ReturnStatus extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['ACCEPTED', 'REJECTED'];
    }
}
