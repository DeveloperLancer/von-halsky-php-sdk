<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

/** Currencies accepted by the currently supported request contract. */
enum Currency: string
{
    case PLN = 'PLN';
}
