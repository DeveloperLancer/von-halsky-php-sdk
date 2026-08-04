<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ExtensibleEnum;

/** Forward-compatible offer status returned by the API. */
final class OfferStatus extends ExtensibleEnum
{
    public const PENDING = 'PENDING';
    public const REJECTED = 'REJECTED';
    public const PUBLISHED = 'PUBLISHED';
    public const CLOSED = 'CLOSED';
    public const SOLDOUT = 'SOLDOUT';

    protected static function knownValues(): array
    {
        return [self::PENDING, self::REJECTED, self::PUBLISHED, self::CLOSED, self::SOLDOUT];
    }
}
