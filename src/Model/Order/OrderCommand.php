<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Order;

use DevLancer\VonHalsky\Model\Offer\CommandStatus;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\CommandId;

/** Accepted or processed asynchronous order command. */
final class OrderCommand implements ResponseDtoInterface
{
    public function __construct(
        public readonly CommandId $commandId,
        public readonly CommandStatus $status,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }

}
