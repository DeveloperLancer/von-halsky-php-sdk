<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\PostSalePatch;
use DevLancer\VonHalsky\Model\Offer\PostSalePolicyPatch;
use DevLancer\VonHalsky\Model\Offer\ProductDimensionsPatch;
use DevLancer\VonHalsky\Model\Offer\ProductPatch;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\ManufacturerProductNumber;
use DevLancer\VonHalsky\ValueObject\Sku;
use PHPUnit\Framework\TestCase;

final class PatchOfferRequestTest extends TestCase
{
    public function testNormalizesACompleteNestedOfferPatchWithoutUndefinedFields(): void
    {
        $request = new PatchOfferRequest(
            externalId: OptionalValue::of('merchant-offer-42'),
            product: OptionalValue::of(new ProductPatch(
                name: OptionalValue::of('Updated product'),
                description: OptionalValue::of('Updated description'),
                brand: OptionalValue::of('Updated brand'),
                categoryId: OptionalValue::of($this->leafCategory()),
                attributes: OptionalValue::of([new AttributeValue('attribute-1', ['red'])]),
                model: OptionalValue::of('Updated model'),
                superModel: OptionalValue::of('Updated super model'),
                sku: OptionalValue::of(new Sku('SKU-42')),
                manufacturerProductNumber: OptionalValue::of(new ManufacturerProductNumber('MPN-42')),
                ean: OptionalValue::of(new Ean('5901234123457')),
                dimension: OptionalValue::of(new ProductDimensionsPatch(
                    width: OptionalValue::of(20),
                    weight: OptionalValue::of(450),
                )),
            )),
            affiliationProductUrl: OptionalValue::of('https://merchant.example/products/42'),
            postSale: OptionalValue::of(new PostSalePatch(
                returnPolicy: OptionalValue::of(new PostSalePolicyPatch(OptionalValue::of('30-day returns'))),
                complaintPolicy: OptionalValue::of(new PostSalePolicyPatch(OptionalValue::of('Contact support first'))),
            )),
        );

        self::assertSame([
            'externalId' => 'merchant-offer-42',
            'product' => [
                'name' => 'Updated product',
                'description' => 'Updated description',
                'brand' => 'Updated brand',
                'categoryId' => 'leaf-category',
                'attributes' => [['id' => 'attribute-1', 'values' => ['red']]],
                'model' => 'Updated model',
                'superModel' => 'Updated super model',
                'sku' => 'SKU-42',
                'manufacturerProductNumber' => 'MPN-42',
                'ean' => '5901234123457',
                'dimension' => ['width' => 20, 'weight' => 450],
            ],
            'affiliationProductUrl' => 'https://merchant.example/products/42',
            'postSale' => [
                'returnPolicy' => ['description' => '30-day returns'],
                'complaintPolicy' => ['description' => 'Contact support first'],
            ],
        ], (new RequestNormalizer())->normalize($request));
    }

    public function testNestedPatchPreservesExplicitNullWithoutSendingUndefinedSiblings(): void
    {
        $request = new PatchOfferRequest(
            product: OptionalValue::of(new ProductPatch(description: OptionalValue::null())),
            affiliationProductUrl: OptionalValue::null(),
            postSale: OptionalValue::of(new PostSalePatch(returnPolicy: OptionalValue::null())),
        );

        self::assertSame([
            'product' => ['description' => null],
            'affiliationProductUrl' => null,
            'postSale' => ['returnPolicy' => null],
        ], (new RequestNormalizer())->normalize($request));
    }

    public function testExternalIdAndEanCannotBeCleared(): void
    {
        $this->assertInvalidField('Offer.externalId', static fn (): object => self::constructWithArguments(PatchOfferRequest::class, [
            'externalId' => OptionalValue::null(),
        ]));
        $this->assertInvalidField('Product.ean', static fn (): object => self::constructWithArguments(ProductPatch::class, [
            'ean' => OptionalValue::null(),
        ]));
    }

    public function testProductPatchRejectsInvalidNestedValues(): void
    {
        $this->assertInvalidField('Product.attributes[0]', static fn (): object => self::constructWithArguments(ProductPatch::class, [
            'attributes' => OptionalValue::of(['invalid']),
        ]));
        $this->assertInvalidField('Product.dimension.width', static fn (): ProductDimensionsPatch => new ProductDimensionsPatch(
            width: OptionalValue::of(0),
        ));
        $this->assertInvalidField('Offer.affiliationProductUrl', static fn (): PatchOfferRequest => new PatchOfferRequest(
            affiliationProductUrl: OptionalValue::of(str_repeat('u', 2049)),
        ));
    }

    public function testProductPatchRejectsANonLeafCategory(): void
    {
        $this->assertInvalidField('categoryId', static fn (): ProductPatch => new ProductPatch(
            categoryId: OptionalValue::of(new Category(
                CategoryId::fromString('parent-category'),
                'Parent',
                false,
                false,
                null,
            )),
        ));
    }

    private function leafCategory(): Category
    {
        return new Category(
            CategoryId::fromString('leaf-category'),
            'Leaf',
            true,
            false,
            null,
        );
    }

    private function assertInvalidField(string $fieldPath, callable $operation): void
    {
        try {
            $operation();
            self::fail(sprintf('Expected invalid request field "%s".', $fieldPath));
        } catch (InvalidRequestException $exception) {
            self::assertSame($fieldPath, $exception->fieldPath);
        }
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    private static function constructWithArguments(string $class, array $arguments): object
    {
        return (new \ReflectionClass($class))->newInstanceArgs($arguments);
    }
}
