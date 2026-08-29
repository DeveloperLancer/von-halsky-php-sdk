<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** A product attribute value. */
final class AttributeValue implements RequestDtoInterface
{
    /** @param list<string> $values */
    public function __construct(public readonly string $id, public readonly array $values, public readonly ?string $language = null)
    {
        if ($id === '') {
            throw new InvalidRequestException('Product.attributes.id', 'must not be empty');
        }
        RequestValidator::attributeValues($values, 'Product.attributes.values');
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'lang' => $this->language, 'values' => $this->values], static fn (mixed $v): bool => $v !== null);
    }
}
