<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model;

/** Marker contract for immutable DTOs hydrated from API responses. */
interface ResponseDtoInterface
{
    /** @return array<string, mixed> Unknown response members retained for diagnostics. */
    public function additionalData(): array;
}
