<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;

final class UpsertAttribute implements AttributeOperation
{
    /** @param list<string> $values */
    public function __construct(public readonly string $id, public readonly array $values, public readonly ?string $language = null)
    {
        if ($id === '') {
            throw new InvalidRequestException('Offer.attributes.id', 'must not be empty');
        }
        RequestValidator::attributeValues($values, 'Offer.attributes.values', RequestValidator::ATTRIBUTE_VALUE_MAX_LENGTH);
    }

    public function jsonSerialize(): array
    {
        return array_filter(['type' => 'upsert', 'id' => $this->id, 'lang' => $this->language, 'values' => $this->values], static fn (mixed $v): bool => $v !== null);
    }
}
