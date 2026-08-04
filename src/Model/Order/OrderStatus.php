<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

final class OrderStatus extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['CREATED', 'ACCEPTED', 'REFUSED', 'REJECTED', 'CANCELED', 'UNKNOWN'];
    }
}
