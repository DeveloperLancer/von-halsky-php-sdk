<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\ManufacturerProductNumber;
use DevLancer\VonHalsky\ValueObject\Sku;

/** Partial product data for an offer merge patch. */
final class ProductPatch implements RequestDtoInterface
{
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $name;
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $description;
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $brand;
    /** @var OptionalValue<CategoryId|null> */
    public readonly OptionalValue $categoryId;
    /** @var OptionalValue<list<AttributeValue>|null> */
    public readonly OptionalValue $attributes;
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $model;
    /** @var OptionalValue<string|null> */
    public readonly OptionalValue $superModel;
    /** @var OptionalValue<Sku|null> */
    public readonly OptionalValue $sku;
    /** @var OptionalValue<ManufacturerProductNumber|null> */
    public readonly OptionalValue $manufacturerProductNumber;
    /** @var OptionalValue<Ean> */
    public readonly OptionalValue $ean;
    /** @var OptionalValue<ProductDimensionsPatch|null> */
    public readonly OptionalValue $dimension;

    /**
     * @param OptionalValue<CategoryId|Category|null>|null $categoryId
     * @param OptionalValue<list<AttributeValue>|null>|null $attributes
     * @param OptionalValue<string|null>|null $name
     * @param OptionalValue<string|null>|null $description
     * @param OptionalValue<string|null>|null $brand
     * @param OptionalValue<string|null>|null $model
     * @param OptionalValue<string|null>|null $superModel
     * @param OptionalValue<Sku|null>|null $sku
     * @param OptionalValue<ManufacturerProductNumber|null>|null $manufacturerProductNumber
     * @param OptionalValue<Ean>|null $ean
     * @param OptionalValue<ProductDimensionsPatch|null>|null $dimension
     */
    public function __construct(
        ?OptionalValue $name = null,
        ?OptionalValue $description = null,
        ?OptionalValue $brand = null,
        ?OptionalValue $categoryId = null,
        ?OptionalValue $attributes = null,
        ?OptionalValue $model = null,
        ?OptionalValue $superModel = null,
        ?OptionalValue $sku = null,
        ?OptionalValue $manufacturerProductNumber = null,
        ?OptionalValue $ean = null,
        ?OptionalValue $dimension = null,
    ) {
        $this->name = $name ?? OptionalValue::undefined();
        $this->description = $description ?? OptionalValue::undefined();
        $this->brand = $brand ?? OptionalValue::undefined();
        $this->categoryId = $this->category($categoryId ?? OptionalValue::undefined());
        $this->attributes = $attributes ?? OptionalValue::undefined();
        $this->model = $model ?? OptionalValue::undefined();
        $this->superModel = $superModel ?? OptionalValue::undefined();
        $this->sku = $sku ?? OptionalValue::undefined();
        $this->manufacturerProductNumber = $manufacturerProductNumber ?? OptionalValue::undefined();
        $this->ean = $ean ?? OptionalValue::undefined();
        $this->dimension = $dimension ?? OptionalValue::undefined();

        $this->validateString($this->name, 150, 'Product.name');
        $this->validateString($this->description, 100000, 'Product.description');
        $this->validateString($this->brand, 100, 'Product.brand');
        $this->validateAttributes();
        $this->validateString($this->model, 100, 'Product.model');
        $this->validateString($this->superModel, 100, 'Product.superModel');
        $this->validateInstance($this->sku, Sku::class, 'Product.sku');
        $this->validateInstance($this->manufacturerProductNumber, ManufacturerProductNumber::class, 'Product.manufacturerProductNumber');
        $this->validateEan();
        $this->validateInstance($this->dimension, ProductDimensionsPatch::class, 'Product.dimension');
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'categoryId' => $this->categoryId,
            'attributes' => $this->attributes,
            'model' => $this->model,
            'superModel' => $this->superModel,
            'sku' => $this->sku,
            'manufacturerProductNumber' => $this->manufacturerProductNumber,
            'ean' => $this->ean,
            'dimension' => $this->dimension,
        ];
    }

    /**
     * @param OptionalValue<CategoryId|Category|null> $categoryId
     * @return OptionalValue<CategoryId|null>
     */
    private function category(OptionalValue $categoryId): OptionalValue
    {
        if (!$categoryId->isDefined()) {
            return OptionalValue::undefined();
        }
        if ($categoryId->isNull()) {
            return OptionalValue::null();
        }
        $value = $categoryId->value();
        if ($value instanceof Category) {
            return OptionalValue::of($value->requireLeaf()->id);
        }
        if ($value instanceof CategoryId) {
            return OptionalValue::of($value);
        }

        throw new InvalidRequestException('Product.categoryId', 'must be a CategoryId or Category');
    }

    /** @param OptionalValue<mixed> $value */
    private function validateString(OptionalValue $value, int $maximum, string $fieldPath): void
    {
        if (!$value->isDefined() || $value->isNull()) {
            return;
        }
        if (!is_string($value->value())) {
            throw new InvalidRequestException($fieldPath, 'must be a string');
        }

        RequestValidator::stringLength($value->value(), 0, $maximum, $fieldPath);
    }

    private function validateAttributes(): void
    {
        if (!$this->attributes->isDefined() || $this->attributes->isNull()) {
            return;
        }
        if (!is_array($this->attributes->value())) {
            throw new InvalidRequestException('Product.attributes', 'must be a list');
        }

        RequestValidator::productAttributes($this->attributes->value(), 'Product.attributes');
    }

    /**
     * @param OptionalValue<mixed> $value
     * @param class-string $type
     */
    private function validateInstance(OptionalValue $value, string $type, string $fieldPath): void
    {
        if ($value->isDefined() && !$value->isNull() && !($value->value() instanceof $type)) {
            throw new InvalidRequestException($fieldPath, sprintf('must be an instance of %s', $type));
        }
    }

    private function validateEan(): void
    {
        if (!$this->ean->isDefined()) {
            return;
        }
        if ($this->ean->isNull()) {
            throw new InvalidRequestException('Product.ean', 'cannot be cleared after it has been assigned');
        }
        if (!($this->ean->value() instanceof Ean)) {
            throw new InvalidRequestException('Product.ean', 'must be an Ean');
        }
    }
}
