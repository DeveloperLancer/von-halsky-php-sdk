<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\PostSale;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;

final class ActionResult implements ResponseDtoInterface
{
    public function __construct(public readonly ?string $message)
    {
    }

    public function additionalData(): array
    {
        return [];
    }
}
