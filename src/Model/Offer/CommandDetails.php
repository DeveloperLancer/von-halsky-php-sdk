<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\CommandId;

final class CommandDetails implements ResponseDtoInterface
{
    /** @param list<CommandError> $errors */
    public function __construct(
        public readonly CommandId $commandId,
        public readonly CommandStatus $status,
        public readonly array $errors = [],
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }

}
