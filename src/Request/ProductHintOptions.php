<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\ManufacturerProductNumber;

final class ProductHintOptions
{
    public function __construct(
        public readonly ?Ean $ean = null,
        public readonly ?ManufacturerProductNumber $manufacturerProductNumber = null,
        public readonly ?string $name = null,
        public readonly int $limit = 10,
        public readonly int $offset = 0,
        public readonly ?ResponseLanguage $language = null,
    ) {
        if ($ean === null && $manufacturerProductNumber === null && $name === null) {
            throw new InvalidRequestException('offerHint', 'requires ean, manufacturerProductNumber or name');
        }
        if ($name === '') {
            throw new InvalidRequestException('offerHint.name', 'must not be empty');
        }
        RequestValidator::integerRange($limit, 0, 30, 'offerHint.limit');
        if ($offset < 0) {
            throw new InvalidRequestException('offerHint.offset', 'must be non-negative');
        }
    }
}
