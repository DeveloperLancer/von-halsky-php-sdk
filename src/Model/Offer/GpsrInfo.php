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
        public readonly ?string $manufacturerName,
        public readonly ?string $manufacturerEmail,
        public readonly ?string $safetyInformation,
        public readonly array $manuals,
    ) {
    }

    public static function notRequired(): self
    {
        return new self(true, null, null, null, []);
    }

    /** @param list<array{title: string, url: string}> $manuals */
    public static function required(string $manufacturerName, string $manufacturerEmail, string $safetyInformation, array $manuals = []): self
    {
        RequestValidator::stringLength($manufacturerName, 1, 500, 'Gpsr.manufacturer.name');
        RequestValidator::stringLength($manufacturerEmail, 3, 500, 'Gpsr.manufacturer.email');
        if (filter_var($manufacturerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DevLancer\VonHalsky\Exception\InvalidRequestException('Gpsr.manufacturer.email', 'must be a valid email address');
        }
        RequestValidator::stringLength($safetyInformation, 1, 100000, 'Gpsr.safetyInformation');
        RequestValidator::itemLimit($manuals, 20, 'Gpsr.manuals');
        foreach ($manuals as $manual) {
            RequestValidator::stringLength($manual['title'], 5, 500, 'Gpsr.manuals.title');
            RequestValidator::stringLength($manual['url'], 9, 2048, 'Gpsr.manuals.url');
        }

        return new self(false, $manufacturerName, $manufacturerEmail, $safetyInformation, $manuals);
    }

    public function jsonSerialize(): array
    {
        if ($this->doesNotRequireGpsrInfo) {
            return ['doesNotRequireGpsrInfo' => true];
        }

        return [
            'doesNotRequireGpsrInfo' => false,
            'manufacturer' => ['name' => $this->manufacturerName, 'email' => $this->manufacturerEmail],
            'safetyInformation' => $this->safetyInformation,
            'manuals' => $this->manuals,
        ];
    }
}
