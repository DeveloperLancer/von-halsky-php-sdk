<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Address;

/** Explicit GPSR data or the contract-sanctioned exemption. */
final class GpsrInfo implements RequestDtoInterface
{
    /** @param list<array{title: string, url: string}> $manuals */
    private function __construct(
        public readonly bool $doesNotRequireGpsrInfo,
        public readonly ?string $manufacturerName,
        public readonly ?Address $manufacturerAddress,
        public readonly ?string $manufacturerUnstructuredAddress,
        public readonly ?string $manufacturerEmail,
        public readonly ?string $manufacturerPhone,
        public readonly ?string $manufacturerResponsiblePerson,
        public readonly ?string $safetyInformation,
        public readonly ?string $batchNumber,
        public readonly ?bool $ceMarking,
        public readonly array $manuals,
    ) {
    }

    public static function notRequired(): self
    {
        return new self(true, null, null, null, null, null, null, null, null, null, []);
    }

    /** @param list<array{title: string, url: string}> $manuals */
    public static function required(
        string $manufacturerName,
        Address $manufacturerAddress,
        string $manufacturerEmail,
        string $safetyInformation,
        array $manuals = [],
        ?string $manufacturerUnstructuredAddress = null,
        ?string $manufacturerPhone = null,
        ?string $manufacturerResponsiblePerson = null,
        ?string $batchNumber = null,
        ?bool $ceMarking = null,
    ): self
    {
        RequestValidator::stringLength($manufacturerName, 1, 500, 'Gpsr.manufacturer.name');
        if ($manufacturerAddress->building === null || $manufacturerAddress->building === '') {
            throw new InvalidRequestException('Gpsr.manufacturer.address.building', 'is required');
        }
        if ($manufacturerUnstructuredAddress !== null) {
            RequestValidator::stringLength($manufacturerUnstructuredAddress, 1, 300, 'Gpsr.manufacturer.unstructuredAddress');
        }
        RequestValidator::stringLength($manufacturerEmail, 3, 500, 'Gpsr.manufacturer.email');
        if (!self::validEmail($manufacturerEmail)) {
            throw new InvalidRequestException('Gpsr.manufacturer.email', 'must be a valid email address');
        }
        if ($manufacturerPhone !== null) {
            RequestValidator::stringLength($manufacturerPhone, 4, 16, 'Gpsr.manufacturer.phone');
            if (preg_match('/^\\+\\d{3,15}$/D', $manufacturerPhone) !== 1) {
                throw new InvalidRequestException('Gpsr.manufacturer.phone', 'must start with + and contain 3 to 15 digits');
            }
        }
        if ($manufacturerResponsiblePerson !== null) {
            RequestValidator::stringLength($manufacturerResponsiblePerson, 1, 500, 'Gpsr.manufacturer.responsiblePerson');
        }
        RequestValidator::stringLength($safetyInformation, 1, 100000, 'Gpsr.safetyInformation');
        if ($batchNumber !== null) {
            RequestValidator::stringLength($batchNumber, 1, 500, 'Gpsr.batchNumber');
        }
        RequestValidator::itemLimit($manuals, 20, 'Gpsr.manuals');
        foreach ($manuals as $manual) {
            RequestValidator::stringLength($manual['title'], 5, 500, 'Gpsr.manuals.title');
            RequestValidator::stringLength($manual['url'], 9, 2048, 'Gpsr.manuals.url');
        }

        return new self(
            false,
            $manufacturerName,
            $manufacturerAddress,
            $manufacturerUnstructuredAddress,
            $manufacturerEmail,
            $manufacturerPhone,
            $manufacturerResponsiblePerson,
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

        $manufacturer = [
            'name' => $this->manufacturerName,
            'address' => $this->manufacturerAddress === null ? null : self::address($this->manufacturerAddress),
            'unstructuredAddress' => $this->manufacturerUnstructuredAddress,
            'email' => $this->manufacturerEmail,
            'phone' => $this->manufacturerPhone,
            'responsiblePerson' => $this->manufacturerResponsiblePerson,
        ];

        return array_filter([
            'doesNotRequireGpsrInfo' => false,
            'manufacturer' => array_filter($manufacturer, static fn (mixed $value): bool => $value !== null),
            'safetyInformation' => $this->safetyInformation,
            'batchNumber' => $this->batchNumber,
            'ceMarking' => $this->ceMarking,
            'manuals' => $this->manuals,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, string> */
    private static function address(Address $address): array
    {
        if ($address->building === null) {
            throw new \LogicException('A required GPSR address must contain a building number.');
        }
        $result = [
            'street' => $address->street,
            'city' => $address->city,
            'postCode' => $address->postalCode,
            'countryCode' => (string) $address->countryCode,
            'building' => $address->building,
        ];
        if ($address->flat !== null) {
            $result['flat'] = $address->flat;
        }
        if ($address->state !== null) {
            $result['state'] = $address->state;
        }

        return $result;
    }

    private static function validEmail(string $email): bool
    {
        if (preg_match('/^[^@\\s]+@[^@\\s]+$/D', $email) !== 1) {
            return false;
        }

        [, $domain] = explode('@', $email, 2);
        $labels = explode('.', $domain);
        if (count($labels) < 2) {
            return false;
        }
        foreach ($labels as $label) {
            if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/D', $label) !== 1) {
                return false;
            }
        }

        return true;
    }
}
