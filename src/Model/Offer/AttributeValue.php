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
        if ($id === '' || $values === []) {
            throw new InvalidRequestException('Product.attributes', 'id and at least one value are required');
        }
        foreach ($values as $value) {
            RequestValidator::stringLength($value, 1, 1024, 'Attribute.values');
        }
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'lang' => $this->language, 'values' => $this->values], static fn (mixed $v): bool => $v !== null);
    }
}
