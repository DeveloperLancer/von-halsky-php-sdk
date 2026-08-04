<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model;

use JsonSerializable;

/** Marker contract for immutable DTOs constructed by SDK users and sent to the API. */
interface RequestDtoInterface extends JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array;
}
