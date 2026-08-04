<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible offer event type returned by the API. */
final class OfferEventType extends ExtensibleEnum
{
    protected static function knownValues(): array
    {
        return ['CREATED', 'REJECTED', 'REOPENED', 'CLOSED', 'PUBLISHED', 'VALIDATION_FAILED', 'SOLDOUT', 'UPDATED'];
    }
}
