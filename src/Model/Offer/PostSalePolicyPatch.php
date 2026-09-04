<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;

/** Partial return or complaint policy for an offer merge patch. */
final class PostSalePolicyPatch implements RequestDtoInterface
{
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $description;

    /** @param OptionalValue<string|null>|null $description */
    public function __construct(?OptionalValue $description = null)
    {
        $this->description = $description ?? OptionalValue::undefined();
        if ($this->description->isDefined() && !$this->description->isNull() && !is_string($this->description->value())) {
            throw new InvalidRequestException('Offer.postSale.policy.description', 'must be a string');
        }
    }

    public function jsonSerialize(): array
    {
        return ['description' => $this->description];
    }
}
