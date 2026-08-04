<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Complete create-offer command payload. */
final class CreateOfferRequest implements RequestDtoInterface
{
    public function __construct(
        public readonly ProductProposal $product,
        public readonly Stock $stock,
        public readonly Price $price,
        public readonly ?GpsrInfo $gpsr = null,
        public readonly ?string $externalId = null,
        public readonly ?int $daysToShip = null,
    ) {
        if ($daysToShip !== null) {
            RequestValidator::integerRange($daysToShip, 0, 60, 'ShippingTime.daysToShip');
        }
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'product' => $this->product,
            'stock' => $this->stock,
            'price' => $this->price,
            'gpsr' => $this->gpsr,
            'externalId' => $this->externalId,
            'shippingTime' => $this->daysToShip === null ? null : ['daysToShip' => $this->daysToShip],
        ], static fn (mixed $value): bool => $value !== null);
    }
}
