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
}
