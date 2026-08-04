<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

final class RemoveAttribute implements AttributeOperation
{
    public function __construct(public readonly string $id)
    {
        if ($id === '') {
            throw new InvalidRequestException('Offer.attributes.id', 'must not be empty');
        }
    }

    public function jsonSerialize(): array
    {
        return ['type' => 'remove', 'id' => $this->id];
    }
}
