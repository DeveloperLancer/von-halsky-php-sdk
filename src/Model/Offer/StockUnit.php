<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

enum StockUnit: string
{
    case UNIT = 'UNIT';
    case PAIR = 'PAIR';
    case SET = 'SET';
    case OTHER = 'OTHER';
}
