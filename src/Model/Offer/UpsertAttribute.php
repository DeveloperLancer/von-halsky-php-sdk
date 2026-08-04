<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;

final class UpsertAttribute implements AttributeOperation
{
    /** @param list<string> $values */
    public function __construct(public readonly string $id, public readonly array $values, public readonly ?string $language = null)
    {
        if ($id === '' || $values === []) {
            throw new InvalidRequestException('Offer.attributes', 'id and at least one value are required');
        }
    }

    public function jsonSerialize(): array
    {
        return array_filter(['type' => 'upsert', 'id' => $this->id, 'lang' => $this->language, 'values' => $this->values], static fn (mixed $v): bool => $v !== null);
    }
}
