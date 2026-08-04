<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

final class RefundStatus extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['PENDING', 'SUCCESS', 'FAILED'];
    }
}
