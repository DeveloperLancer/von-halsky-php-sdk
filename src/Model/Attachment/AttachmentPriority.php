<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Attachment;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\ValueObject\AttachmentId;

final class AttachmentPriority implements RequestDtoInterface
{
    public function __construct(
        public readonly AttachmentId $attachmentId,
        public readonly int $priority,
    ) {
        if ($priority < 1 || $priority > 1000) {
            throw new InvalidRequestException('attachment.priority', 'must be between 1 and 1000');
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'attachmentId' => $this->attachmentId->value,
            'priority' => $this->priority,
        ];
    }
}
