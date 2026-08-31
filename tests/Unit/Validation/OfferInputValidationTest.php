<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\BatchCreateOffersRequest;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Model\Offer\UpsertAttribute;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\CountryCode;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\Sku;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OfferInputValidationTest extends TestCase
{
    public function testProductNameAndDescriptionMeetOfficialFormMinimums(): void
    {
        new ProductProposal(
            'Product',
            str_repeat('Description ', 10),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
        self::addToAssertionCount(1);
    }

    public function testProductFieldsAllowDocumentedMaximums(): void
    {
        $product = new ProductProposal(
            str_repeat('N', 150),
            str_repeat('D', 100000),
            str_repeat('B', 100),
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
            sku: new Sku(str_repeat('S', 100)),
            model: str_repeat('M', 100),
            superModel: str_repeat('U', 100),
        );

        self::assertSame(150, mb_strlen($product->name));
        self::assertSame(100000, mb_strlen($product->description));
        self::assertSame(100, mb_strlen($product->brand));
    }

    public function testProductNameRejectsMoreThanOneHundredAndFiftyCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            str_repeat('N', 151),
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }

    public function testProductDescriptionRejectsMoreThanOneHundredThousandCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            'Product',
            str_repeat('D', 100001),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }

    public function testProductBrandRejectsMoreThanOneHundredCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            'Product',
            str_repeat('D', 100),
            str_repeat('B', 101),
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }

    public function testModelAndSuperModelRejectMoreThanOneHundredCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            'Product',
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
            model: str_repeat('M', 101),
        );
    }

    public function testProductNameRejectsFewerThanSevenCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            'Item 1',
            str_repeat('Description ', 10),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }

    public function testProductDescriptionRejectsFewerThanOneHundredCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new ProductProposal(
            'Product',
            str_repeat('Short ', 16),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }

    public function testImagesRequireOneToTwentySupportedFiles(): void
    {
        $image = new OfferImage('product.webp', 'https://example.com/product.webp', 1);
        RequestValidator::offerImages([$image]);
        RequestValidator::offerImages(array_fill(0, 20, $image));
        self::addToAssertionCount(2);
    }

    public function testImagesRejectAnEmptyList(): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::offerImages([]);
    }

    public function testImagesRejectMoreThanTwentyItems(): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::offerImages(array_fill(0, 21, new OfferImage('product.webp', 'https://example.com/product.webp', 1)));
    }

    public function testImageRejectsAnUnsupportedExtension(): void
    {
        $this->expectException(InvalidRequestException::class);
        new OfferImage('product.jpeg', 'https://example.com/product.jpeg', 1);
    }

    public function testSkuAllowsAtMostOneHundredCharacters(): void
    {
        self::assertSame(str_repeat('S', 100), (string) new Sku(str_repeat('S', 100)));

        $this->expectException(InvalidRequestException::class);
        new Sku(str_repeat('S', 101));
    }

    public function testSkuIsIncludedInTheProductPayload(): void
    {
        $product = new ProductProposal(
            'Product',
            str_repeat('Description ', 10),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
            sku: new Sku('PRODUCT-001'),
        );

        self::assertSame('PRODUCT-001', (new RequestNormalizer())->normalize($product)['sku']);
    }

    public function testExternalIdRejectsMoreThanTwoHundredAndFiftyFiveCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        new CreateOfferRequest(
            self::minimumProduct(),
            new Stock(1),
            new Price(Money::fromDecimal('1.00'), '23%'),
            externalId: str_repeat('E', 256),
            images: [new OfferImage('product.webp', 'https://example.com/product.webp', 1)],
        );
    }

    public function testAttributeRequestDtosPreserveAnEmptyValueList(): void
    {
        self::assertSame([
            'id' => 'attribute-1',
            'values' => [],
        ], (new AttributeValue('attribute-1', []))->jsonSerialize());
        self::assertSame([
            'type' => 'upsert',
            'id' => 'attribute-1',
            'values' => [],
        ], (new UpsertAttribute('attribute-1', []))->jsonSerialize());
    }

    public function testAttributeUpsertRejectsAValueAboveTheCommonContractMaximum(): void
    {
        $this->expectException(InvalidRequestException::class);
        new UpsertAttribute('attribute-1', [str_repeat('ą', 1025)]);
    }

    public function testRequiredGpsrSerializesNameAddressAndEmail(): void
    {
        $gpsr = GpsrInfo::required(
            'Example manufacturer',
            new Address('Example Street', 'Warsaw', '00-001', new CountryCode('pl'), '10'),
            'manufacturer@example.com',
            'Keep this product away from children.',
        );

        self::assertSame([
            'doesNotRequireGpsrInfo' => false,
            'manufacturer' => [
                'name' => 'Example manufacturer',
                'address' => [
                    'street' => 'Example Street',
                    'city' => 'Warsaw',
                    'postCode' => '00-001',
                    'countryCode' => 'PL',
                    'building' => '10',
                ],
                'email' => 'manufacturer@example.com',
            ],
            'safetyInformation' => 'Keep this product away from children.',
            'manuals' => [],
        ], $gpsr->jsonSerialize());
    }

    public function testRequiredGpsrSupportsAllBoundedContractFields(): void
    {
        $gpsr = GpsrInfo::required(
            str_repeat('M', 500),
            new Address(str_repeat('S', 255), str_repeat('C', 255), '1234567890', new CountryCode('PL'), '1234567890', '1234567890', str_repeat('R', 100)),
            self::maximumLengthEmail(),
            str_repeat('I', 100000),
            [[
                'title' => str_repeat('T', 500),
                'url' => 'https://' . str_repeat('u', 2037) . '.pl',
            ]],
            str_repeat('A', 300),
            '+481234567890123',
            str_repeat('P', 500),
            str_repeat('B', 500),
            true,
        );

        $serialized = $gpsr->jsonSerialize();
        self::assertFalse($serialized['doesNotRequireGpsrInfo']);
        self::assertArrayHasKey('manufacturer', $serialized);
        self::assertArrayHasKey('batchNumber', $serialized);
        self::assertArrayHasKey('ceMarking', $serialized);
        self::assertArrayHasKey('manuals', $serialized);
        self::assertSame(str_repeat('M', 500), $gpsr->manufacturerName);
        self::assertSame('+481234567890123', $gpsr->manufacturerPhone);
        self::assertSame(str_repeat('A', 300), $gpsr->manufacturerUnstructuredAddress);
        self::assertSame(str_repeat('P', 500), $gpsr->manufacturerResponsiblePerson);
        self::assertSame(str_repeat('B', 500), $gpsr->batchNumber);
        self::assertTrue($gpsr->ceMarking);
        self::assertSame(2048, strlen($gpsr->manuals[0]['url']));
    }

    #[DataProvider('validManufacturerEmailProvider')]
    public function testRequiredGpsrAcceptsValidManufacturerEmail(string $email): void
    {
        $gpsr = GpsrInfo::required(
            'Example manufacturer',
            self::manufacturerAddress(),
            $email,
            'Safe product.',
        );

        self::assertSame($email, $gpsr->manufacturerEmail);
    }

    #[DataProvider('invalidManufacturerEmailProvider')]
    public function testRequiredGpsrRejectsInvalidManufacturerEmail(string $email): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gpsr.manufacturer.email');

        GpsrInfo::required('Example manufacturer', self::manufacturerAddress(), $email, 'Safe product.');
    }

    public function testRequiredGpsrRejectsMalformedManualEntries(): void
    {
        foreach ([
            ['manual' => ['title' => 'Manual title', 'url' => 'https://example.com/manual.pdf']],
            ['not-an-object'],
            [[]],
            [['title' => 123, 'url' => 'https://example.com/manual.pdf']],
            [['title' => 'Manual title', 'url' => 123]],
        ] as $manuals) {
            try {
                GpsrInfo::required('Example manufacturer', self::manufacturerAddress(), 'manufacturer@example.com', 'Safe product.', $manuals);
                self::fail('Expected malformed GPSR manuals to be rejected.');
            } catch (InvalidRequestException $exception) {
                self::assertStringStartsWith('Gpsr.manuals', $exception->fieldPath);
            }
        }
    }

    public function testRequestDtoListsRejectAssociativeAndWrongTypeEntries(): void
    {
        $attribute = new AttributeValue('attribute-1', ['value']);
        $image = new OfferImage('product.webp', 'https://example.com/product.webp', 1);
        $request = new CreateOfferRequest(self::minimumProduct(), new Stock(1), new Price(Money::fromDecimal('1.00'), '23%'), images: [$image]);

        self::assertInvalidRequestField('Product.attributes', static fn (): object => self::constructWithInvalidArguments(ProductProposal::class, [
            'name' => 'Product',
            'description' => str_repeat('D', 100),
            'brand' => 'Brand',
            'categoryId' => CategoryId::fromString('leaf-1'),
            'ean' => new Ean('5901234123457'),
            'attributes' => ['attribute' => $attribute],
        ]));
        self::assertInvalidRequestField('Offer.images[0]', static fn (): object => self::constructWithInvalidArguments(CreateOfferRequest::class, [
            'product' => self::minimumProduct(),
            'stock' => new Stock(1),
            'price' => new Price(Money::fromDecimal('1.00'), '23%'),
            'images' => ['invalid'],
        ]));
        self::assertInvalidRequestField('Offer.images', static fn (): object => self::constructWithInvalidArguments(PatchOfferRequest::class, [
            'images' => OptionalValue::of(['image' => $image]),
        ]));
        self::assertInvalidRequestField('BatchOffers', static fn (): object => self::constructWithInvalidArguments(BatchCreateOffersRequest::class, [
            'offers' => ['offer' => $request],
        ]));
        self::assertInvalidRequestField('Offer.attributes.operations[0]', static fn (): object => self::constructWithInvalidArguments(OfferAttributesPatch::class, [
            'operations' => ['invalid'],
        ]));
    }

    public function testRequiredGpsrRejectsValuesOverDocumentedMaximums(): void
    {
        $this->expectException(InvalidRequestException::class);
        GpsrInfo::required(
            str_repeat('M', 501),
            new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10'),
            'manufacturer@example.com',
            'Safe product.',
        );
    }

    public function testRequiredGpsrRejectsAnInvalidManufacturerPhone(): void
    {
        $this->expectException(InvalidRequestException::class);
        GpsrInfo::required(
            'Example manufacturer',
            new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10'),
            'manufacturer@example.com',
            'Safe product.',
            manufacturerPhone: '48123456789',
        );
    }

    public function testRequiredGpsrRejectsSafetyInformationOverOneHundredThousandCharacters(): void
    {
        $this->expectException(InvalidRequestException::class);
        GpsrInfo::required(
            'Example manufacturer',
            new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10'),
            'manufacturer@example.com',
            str_repeat('S', 100001),
        );
    }

    public function testRequiredGpsrRejectsAnAddressWithoutBuildingNumber(): void
    {
        $this->expectException(InvalidRequestException::class);
        GpsrInfo::required(
            'Example manufacturer',
            new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL')),
            'manufacturer@example.com',
            'Keep this product away from children.',
        );
    }

    public function testAddressPostCodeUsesTheDocumentedThreeToTenCharacterRange(): void
    {
        new Address('Example Street', 'Warsaw', '1234567890', new CountryCode('PL'), '10');
        self::addToAssertionCount(1);

        $this->expectException(InvalidRequestException::class);
        new Address('Example Street', 'Warsaw', '12', new CountryCode('PL'), '10');
    }

    /** @return iterable<string, array{string}> */
    public static function validManufacturerEmailProvider(): iterable
    {
        yield 'ordinary address' => ['manufacturer@example.com'];
        yield 'plus tag' => ['manufacturer+gpsr@example.co.uk'];
        yield 'maximum RFC length' => [self::maximumLengthEmail()];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidManufacturerEmailProvider(): iterable
    {
        yield 'missing at sign' => ['manufacturer.example.com'];
        yield 'empty local part' => ['@example.com'];
        yield 'empty domain' => ['manufacturer@'];
        yield 'space' => ['manufacturer name@example.com'];
        yield 'dotless domain' => ['manufacturer@localhost'];
        yield 'invalid domain label' => ['manufacturer@-example.com'];
        yield 'double domain dot' => ['manufacturer@example..com'];
        yield 'unicode local part' => ['mąnufacturer@example.com'];
    }

    private static function maximumLengthEmail(): string
    {
        return str_repeat('a', 64) . '@'
            . str_repeat('b', 63) . '.'
            . str_repeat('c', 63) . '.'
            . str_repeat('d', 61);
    }

    private static function manufacturerAddress(): Address
    {
        return new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10');
    }

    private static function assertInvalidRequestField(string $fieldPath, callable $operation): void
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
    private static function constructWithInvalidArguments(string $class, array $arguments): object
    {
        return (new \ReflectionClass($class))->newInstanceArgs($arguments);
    }

    private static function minimumProduct(): ProductProposal
    {
        return new ProductProposal(
            'Product',
            str_repeat('D', 100),
            'Brand',
            CategoryId::fromString('leaf-1'),
            new Ean('5901234123457'),
        );
    }
}
