<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Money;

/** Gross price and tax information. */
final class Price implements RequestDtoInterface
{
    public function __construct(public readonly Money $grossPrice, public readonly string $taxRateInfo)
    {
        RequestValidator::stringLength($taxRateInfo, 1, 100, 'Price.taxRateInfo');
    }

    public function jsonSerialize(): array
    {
        return ['grossPrice' => $this->grossPrice, 'taxRateInfo' => $this->taxRateInfo];
    }
}
