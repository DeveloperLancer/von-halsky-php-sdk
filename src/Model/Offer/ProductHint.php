<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

final class ProductHint implements ResponseDtoInterface
{
    /**
     * @param array<string, mixed>       $product
     * @param list<array<string, mixed>> $gpsr
     */
    public function __construct(public readonly array $product, public readonly array $gpsr)
    {
    }

    public function additionalData(): array
    {
        return [];
    }
}
