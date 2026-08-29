<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Attachment;

enum AttachmentType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case WARRANTY_CARD = 'WARRANTY_CARD';
    case MANUAL = 'MANUAL';
    case ASSEMBLY_INSTRUCTION = 'ASSEMBLY_INSTRUCTION';
    case SPECIFICATION_SHEET = 'SPECIFICATION_SHEET';
    case CERTIFICATE = 'CERTIFICATE';
    case SIZE_CHART = 'SIZE_CHART';
    case PRODUCT_LABEL = 'PRODUCT_LABEL';
    case ENERGY_LABEL = 'ENERGY_LABEL';
    case GENERAL_DOCUMENT = 'GENERAL_DOCUMENT';
    case OTHER = 'OTHER';

    public function allowsMimeType(string $mimeType): bool
    {
        $images = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = strtolower($mimeType);

        return match ($this) {
            self::IMAGE => in_array($mimeType, $images, true),
            self::VIDEO => in_array($mimeType, ['video/mp4', 'video/mpeg'], true),
            self::AUDIO => in_array($mimeType, ['audio/mpeg', 'audio/wav'], true),
            self::WARRANTY_CARD,
            self::MANUAL,
            self::ASSEMBLY_INSTRUCTION,
            self::CERTIFICATE,
            self::GENERAL_DOCUMENT => $mimeType === 'application/pdf',
            self::SPECIFICATION_SHEET,
            self::SIZE_CHART,
            self::PRODUCT_LABEL,
            self::ENERGY_LABEL => $mimeType === 'application/pdf' || in_array($mimeType, $images, true),
            self::OTHER => $mimeType === 'application/pdf'
                || in_array($mimeType, $images, true)
                || in_array($mimeType, ['video/mp4', 'video/mpeg', 'audio/mpeg', 'audio/wav'], true),
        };
    }
}
