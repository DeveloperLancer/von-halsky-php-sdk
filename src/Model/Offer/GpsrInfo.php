<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Explicit GPSR data or the contract-sanctioned exemption. */
final class GpsrInfo implements RequestDtoInterface
{
    /** @param list<array{title: string, url: string}> $manuals */
    private function __construct(
        public readonly bool $doesNotRequireGpsrInfo,
        public readonly ?Manufacturer $manufacturer,
        public readonly ?string $safetyInformation,
        public readonly ?string $batchNumber,
        public readonly ?bool $ceMarking,
        public readonly array $manuals,
    ) {
    }

    public static function notRequired(): self
    {
        return new self(true, null, null, null, null, []);
    }

    /** @param array<mixed> $manuals */
    public static function required(
        Manufacturer $manufacturer,
        string $safetyInformation,
        array $manuals = [],
        ?string $batchNumber = null,
        ?bool $ceMarking = null,
    ): self {
        RequestValidator::stringLength($safetyInformation, 1, 100000, 'Gpsr.safetyInformation');
        if ($batchNumber !== null) {
            RequestValidator::stringLength($batchNumber, 1, 500, 'Gpsr.batchNumber');
        }
        RequestValidator::gpsrManuals($manuals, 'Gpsr.manuals');
        foreach ($manuals as $index => $manual) {
            RequestValidator::stringLength($manual['title'], 5, 500, sprintf('Gpsr.manuals[%d].title', $index));
            RequestValidator::stringLength($manual['url'], 9, 2048, sprintf('Gpsr.manuals[%d].url', $index));
        }

        return new self(
            false,
            $manufacturer,
            $safetyInformation,
            $batchNumber,
            $ceMarking,
            $manuals,
        );
    }

    public function jsonSerialize(): array
    {
        if ($this->doesNotRequireGpsrInfo) {
            return ['doesNotRequireGpsrInfo' => true];
        }

        return array_filter([
            'doesNotRequireGpsrInfo' => false,
            'manufacturer' => $this->manufacturer === null ? null : $this->manufacturer->jsonSerialize(),
            'safetyInformation' => $this->safetyInformation,
            'batchNumber' => $this->batchNumber,
            'ceMarking' => $this->ceMarking,
            'manuals' => $this->manuals,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
