<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;

/** Partial product dimensions for an offer merge patch. */
final class ProductDimensionsPatch implements RequestDtoInterface
{
    /** @var OptionalValue<int|null> */
    public readonly OptionalValue $width;
    /** @var OptionalValue<int|null> */
    public readonly OptionalValue $height;
    /** @var OptionalValue<int|null> */
    public readonly OptionalValue $length;
    /** @var OptionalValue<int|null> */
    public readonly OptionalValue $weight;

    /**
     * @param OptionalValue<int|null>|null $width
     * @param OptionalValue<int|null>|null $height
     * @param OptionalValue<int|null>|null $length
     * @param OptionalValue<int|null>|null $weight
     */
    public function __construct(
        ?OptionalValue $width = null,
        ?OptionalValue $height = null,
        ?OptionalValue $length = null,
        ?OptionalValue $weight = null,
    ) {
        $this->width = $width ?? OptionalValue::undefined();
        $this->height = $height ?? OptionalValue::undefined();
        $this->length = $length ?? OptionalValue::undefined();
        $this->weight = $weight ?? OptionalValue::undefined();

        $this->validateDimension($this->width, 'width', 1, 10000);
        $this->validateDimension($this->height, 'height', 1, 10000);
        $this->validateDimension($this->length, 'length', 1, 10000);
        $this->validateDimension($this->weight, 'weight', 1, 1000000);
    }

    public function jsonSerialize(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'weight' => $this->weight,
        ];
    }

    /** @param OptionalValue<int|null> $value */
    private function validateDimension(OptionalValue $value, string $name, int $minimum, int $maximum): void
    {
        if (!$value->isDefined() || $value->isNull()) {
            return;
        }
        if (!is_int($value->value())) {
            throw new InvalidRequestException('Product.dimension.' . $name, 'must be an integer');
        }

        RequestValidator::integerRange($value->value(), $minimum, $maximum, 'Product.dimension.' . $name);
    }
}
