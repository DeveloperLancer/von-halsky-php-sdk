<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Attachment;

use DateTimeImmutable;
use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use DevLancer\VonHalsky\ValueObject\AttachmentId;

final class AttachmentInfo implements ResponseDtoInterface
{
    public function __construct(
        public readonly AttachmentId $id,
        public readonly string $name,
        public readonly string $type,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?string $url,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
