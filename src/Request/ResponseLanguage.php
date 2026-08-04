<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

/** Languages accepted by the current organization and category endpoints. */
enum ResponseLanguage: string
{
    case POLISH = 'pl';
    case ENGLISH = 'en';
}
