<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** Result of creating a synthetic Stage offer and waiting until it is readable. */
final class StageCreatedOffer
{
    public function __construct(
        public readonly OfferId $offerId,
        public readonly CommandId $createCommandId,
        public readonly int $createStatusCode,
        public readonly OfferDetails $offer,
        public readonly string $externalId,
    ) {
    }
}
