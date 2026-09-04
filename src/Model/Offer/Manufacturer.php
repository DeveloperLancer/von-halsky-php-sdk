<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CountryCode;

/** GPSR manufacturer details supplied with an offer. */
final class Manufacturer implements RequestDtoInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?CountryCode $countryCode = null,
        public readonly ?Address $address = null,
        public readonly ?string $unstructuredAddress = null,
        public readonly ?ResponsiblePerson $responsiblePersonDetails = null,
    ) {
        RequestValidator::stringLength($name, 1, 500, 'Manufacturer.name');
        self::validateEmail($email, 'Manufacturer.email');
        if ($phone !== null) {
            self::validatePhone($phone, 'Manufacturer.phone');
        }
        if ($address !== null && $address->building === null) {
            throw new InvalidRequestException('Manufacturer.address.building', 'is required');
        }
        if ($unstructuredAddress !== null) {
            RequestValidator::stringLength($unstructuredAddress, 0, 300, 'Manufacturer.unstructuredAddress');
        }
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'countryCode' => $this->countryCode === null ? null : (string) $this->countryCode,
            'address' => $this->address === null ? null : self::address($this->address),
            'unstructuredAddress' => $this->unstructuredAddress,
            'responsiblePersonDetails' => $this->responsiblePersonDetails === null ? null : $this->responsiblePersonDetails->jsonSerialize(),
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function validateEmail(string $email, string $fieldPath): void
    {
        RequestValidator::stringLength($email, 3, 500, $fieldPath);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidRequestException($fieldPath, 'must be a valid email address');
        }
    }

    private static function validatePhone(string $phone, string $fieldPath): void
    {
        RequestValidator::stringLength($phone, 4, 16, $fieldPath);
        if (preg_match('/^\\+\\d{3,15}$/D', $phone) !== 1) {
            throw new InvalidRequestException($fieldPath, 'must start with + and contain 3 to 15 digits');
        }
    }

    /** @return array<string, string> */
    private static function address(Address $address): array
    {
        $result = [
            'street' => $address->street,
            'city' => $address->city,
            'postCode' => $address->postalCode,
            'countryCode' => (string) $address->countryCode,
        ];
        if ($address->building !== null) {
            $result['building'] = $address->building;
        }
        if ($address->flat !== null) {
            $result['flat'] = $address->flat;
        }
        if ($address->state !== null) {
            $result['state'] = $address->state;
        }

        return $result;
    }
}
