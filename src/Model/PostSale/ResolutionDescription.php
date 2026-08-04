<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\PostSale;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;

final class ResolutionDescription implements RequestDtoInterface
{
    public function __construct(public readonly string $description)
    {
        if (strlen($description) > 1000) {
            throw new InvalidRequestException('claim.description', 'must contain at most 1000 bytes');
        }
    }

    public function jsonSerialize(): array
    {
        return ['description' => $this->description];
    }
}
