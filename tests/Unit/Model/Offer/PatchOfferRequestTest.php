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
        $description = str_repeat('Updated product description. ', 4);
        $request = new PatchOfferRequest(
            externalId: OptionalValue::of('merchant-offer-42'),
            product: OptionalValue::of(new ProductPatch(
                name: OptionalValue::of('Updated product'),
                description: OptionalValue::of($description),
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
                'description' => $description,
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
            product: OptionalValue::of(new ProductPatch(model: OptionalValue::null())),
            affiliationProductUrl: OptionalValue::null(),
            postSale: OptionalValue::of(new PostSalePatch(returnPolicy: OptionalValue::null())),
        );

        self::assertSame([
            'product' => ['model' => null],
            'affiliationProductUrl' => null,
            'postSale' => ['returnPolicy' => null],
        ], (new RequestNormalizer())->normalize($request));
    }

    public function testExternalIdAndEanCannotBeCleared(): void
    {
        $this->assertInvalidField('Offer.externalId', static fn (): object => self::constructWithArguments(PatchOfferRequest::class, [
            'externalId' => OptionalValue::null(),
        ]), 'must be omitted or assigned a non-null value');
        $this->assertInvalidField('Product.ean', static fn (): object => self::constructWithArguments(ProductPatch::class, [
            'ean' => OptionalValue::null(),
        ]), 'must be omitted or assigned a non-null value');
    }

    public function testRequiredOfferMembersCannotBeCleared(): void
    {
        foreach ([
            'product' => 'Offer.product',
            'price' => 'Offer.price',
            'stock' => 'Offer.stock',
        ] as $argument => $fieldPath) {
            $this->assertInvalidField($fieldPath, static fn (): object => self::constructWithArguments(PatchOfferRequest::class, [
                $argument => OptionalValue::null(),
            ]));
        }
    }

    public function testRequiredProductMembersCannotBeCleared(): void
    {
        foreach (['name', 'description', 'brand', 'categoryId'] as $argument) {
            $this->assertInvalidField('Product.' . $argument, static fn (): object => self::constructWithArguments(ProductPatch::class, [
                $argument => OptionalValue::null(),
            ]));
        }
    }

    public function testProductTextLimitsMatchProductProposal(): void
    {
        foreach ([
            ['name', 'Product.name', str_repeat('n', 6)],
            ['name', 'Product.name', str_repeat('n', 151)],
            ['description', 'Product.description', str_repeat('d', 99)],
            ['description', 'Product.description', str_repeat('d', 100001)],
            ['brand', 'Product.brand', ''],
            ['brand', 'Product.brand', str_repeat('b', 101)],
            ['model', 'Product.model', ''],
            ['model', 'Product.model', str_repeat('m', 101)],
            ['superModel', 'Product.superModel', ''],
            ['superModel', 'Product.superModel', str_repeat('s', 101)],
        ] as [$argument, $fieldPath, $invalidValue]) {
            $this->assertInvalidField($fieldPath, static fn (): object => self::constructWithArguments(ProductPatch::class, [
                $argument => OptionalValue::of($invalidValue),
            ]));
        }

        new ProductPatch(
            name: OptionalValue::of(str_repeat('n', 150)),
            description: OptionalValue::of(str_repeat('d', 100000)),
            brand: OptionalValue::of(str_repeat('b', 100)),
            model: OptionalValue::of(str_repeat('m', 100)),
            superModel: OptionalValue::of(str_repeat('s', 100)),
        );
        self::addToAssertionCount(1);
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

    private function assertInvalidField(string $fieldPath, callable $operation, ?string $reason = null): void
    {
        try {
            $operation();
            self::fail(sprintf('Expected invalid request field "%s".', $fieldPath));
        } catch (InvalidRequestException $exception) {
            self::assertSame($fieldPath, $exception->fieldPath);
            if ($reason !== null) {
                self::assertSame($reason, $exception->reason);
            }
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
