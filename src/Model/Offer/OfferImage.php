<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Publicly reachable image associated with an offer. */
final class OfferImage implements RequestDtoInterface
{
    public function __construct(
        public readonly string $fileName,
        public readonly string $fileUrl,
        public readonly int $priority,
    ) {
        RequestValidator::stringLength($fileName, 5, 500, 'Offer.images.fileName');
        RequestValidator::offerImageFileName($fileName);
        RequestValidator::stringLength($fileUrl, 9, 2048, 'Offer.images.fileUrl');
        if ($priority < 1) {
            throw new InvalidRequestException('Offer.images.priority', 'must be at least 1');
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'fileName' => $this->fileName,
            'fileUrl' => $this->fileUrl,
            'priority' => $this->priority,
        ];
    }
}
