<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

final class OrderEventType extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['CREATED', 'ACCEPTED', 'REFUSED', 'REJECTED', 'CANCELLED', 'PAID', 'PARTIALLY_REFUNDED', 'REFUNDED', 'SHIPPED', 'RETURN_CREATED', 'UPDATED'];
    }
}
