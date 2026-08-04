<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Attachment;

use DevLancer\VonHalsky\Model\ResponseDtoInterface;
use Psr\Http\Message\StreamInterface;

/** Streamed download. The caller owns and must close the stream. */
final class DownloadedAttachment implements ResponseDtoInterface
{
    public function __construct(
        public readonly StreamInterface $stream,
        public readonly ?string $contentType,
        public readonly ?string $filename,
        public readonly ?int $size,
    ) {
    }

    public function additionalData(): array
    {
        return [];
    }
}
