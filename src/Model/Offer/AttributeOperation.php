<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use JsonSerializable;

/** @internal Public implementations preserve operation order. */
interface AttributeOperation extends JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array;
}
