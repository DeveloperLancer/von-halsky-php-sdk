<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\ValueObject;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Validated postal address fields shared by request models. */
final class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly CountryCode $countryCode,
        public readonly ?string $building = null,
        public readonly ?string $flat = null,
        public readonly ?string $state = null,
    ) {
        RequestValidator::stringLength($street, 2, 255, 'Address.street');
        RequestValidator::stringLength($city, 2, 255, 'Address.city');
        RequestValidator::stringLength($postalCode, 3, 10, 'Address.postalCode');
        if ($building !== null) {
            RequestValidator::stringLength($building, 0, 10, 'Address.building');
        }
        if ($flat !== null) {
            RequestValidator::stringLength($flat, 0, 10, 'Address.flat');
        }
        if ($state !== null) {
            RequestValidator::stringLength($state, 0, 100, 'Address.state');
        }
    }
}
