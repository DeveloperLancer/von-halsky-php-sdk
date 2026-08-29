<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Up to 500 create-offer commands. */
final class BatchCreateOffersRequest implements RequestDtoInterface
{
    /** @param list<CreateOfferRequest> $offers */
    public function __construct(public readonly array $offers)
    {
        RequestValidator::offerBatch($offers, 'BatchOffers');
        $count = count($offers);
        if ($count < 1 || $count > 500) {
            throw new InvalidRequestException('BatchOffers', 'must contain between 1 and 500 offers');
        }
    }

    /** @return list<CreateOfferRequest> The API batch body is a JSON array. */
    public function items(): array
    {
        return $this->offers;
    }

    public function jsonSerialize(): array
    {
        return ['offers' => $this->offers];
    }
}
