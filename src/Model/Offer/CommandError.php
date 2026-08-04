<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

final class CommandError implements ResponseDtoInterface
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $fieldName = null,
        public readonly ?string $elementId = null,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
