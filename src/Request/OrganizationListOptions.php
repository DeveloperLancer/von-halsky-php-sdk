<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

/** Optional localization for the organization list. */
final class OrganizationListOptions
{
    public function __construct(public readonly ?ResponseLanguage $language = null)
    {
    }
}
