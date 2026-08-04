<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Claim;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

final class ClaimState extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['APPROVED', 'REJECTED', 'RESOLUTION_IN_PROGRESS'];
    }
}
