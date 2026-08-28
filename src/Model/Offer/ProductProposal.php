<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Dimensions;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\ManufacturerProductNumber;
use DevLancer\VonHalsky\ValueObject\Sku;
use DevLancer\VonHalsky\ValueObject\Weight;

/** Product data used while creating an offer. */
final class ProductProposal implements RequestDtoInterface
{
    public readonly CategoryId $categoryId;

    /** @param list<AttributeValue> $attributes */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $brand,
        CategoryId|Category $categoryId,
        public readonly ?Ean $ean = null,
        public readonly ?ManufacturerProductNumber $manufacturerProductNumber = null,
        public readonly ?Dimensions $dimensions = null,
        public readonly ?Weight $weight = null,
        public readonly array $attributes = [],
        public readonly ?Sku $sku = null,
        public readonly ?string $model = null,
        public readonly ?string $superModel = null,
    ) {
        $this->categoryId = $categoryId instanceof Category ? $categoryId->requireLeaf()->id : $categoryId;
        RequestValidator::stringLength($name, 7, 150, 'Product.name');
        RequestValidator::stringLength($description, 100, 100000, 'Product.description');
        RequestValidator::stringLength($brand, 1, 100, 'Product.brand');
        if ($model !== null) {
            RequestValidator::stringLength($model, 1, 100, 'Product.model');
        }
        if ($superModel !== null) {
            RequestValidator::stringLength($superModel, 1, 100, 'Product.superModel');
        }
        if ($ean === null && $manufacturerProductNumber === null) {
            throw new InvalidRequestException('Product', 'requires ean or manufacturerProductNumber');
        }
        RequestValidator::productAttributes($attributes, 'Product.attributes');
    }

    public function jsonSerialize(): array
    {
        $dimension = [];
        if ($this->dimensions !== null) {
            $dimension = [
                'width' => $this->dimensions->width,
                'height' => $this->dimensions->height,
                'length' => $this->dimensions->length,
            ];
        }
        if ($this->weight !== null) {
            $dimension['weight'] = $this->weight->grams;
        }

        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'categoryId' => $this->categoryId,
            'ean' => $this->ean,
            'manufacturerProductNumber' => $this->manufacturerProductNumber,
            'sku' => $this->sku,
            'dimension' => $dimension === [] ? null : $dimension,
            'attributes' => $this->attributes === [] ? null : $this->attributes,
            'model' => $this->model,
            'superModel' => $this->superModel,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
