<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

final class OfferAttributesPatch implements RequestDtoInterface
{
    /** @param list<AttributeOperation> $operations */
    public function __construct(public readonly array $operations)
    {
        RequestValidator::offerAttributeOperations($operations);
        if ($operations === []) {
            throw new InvalidRequestException('Offer.attributes.operations', 'must not be empty');
        }
    }

    public function jsonSerialize(): array
    {
        return ['operations' => $this->operations];
    }
}
