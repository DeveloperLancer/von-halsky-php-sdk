<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Model\Offer\UpsertAttribute;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\CountryCode;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\Sku;
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
            self::fiveHundredCharacterEmail(),
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
        self::assertSame(str_repeat('M', 500), $serialized['manufacturer']['name']);
        self::assertSame('+481234567890123', $serialized['manufacturer']['phone']);
        self::assertSame(str_repeat('A', 300), $serialized['manufacturer']['unstructuredAddress']);
        self::assertSame(str_repeat('P', 500), $serialized['manufacturer']['responsiblePerson']);
        self::assertSame(str_repeat('B', 500), $serialized['batchNumber']);
        self::assertTrue($serialized['ceMarking']);
        self::assertSame(2048, strlen($serialized['manuals'][0]['url']));
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

    private static function fiveHundredCharacterEmail(): string
    {
        return str_repeat('a', 64) . '@'
            . str_repeat('b', 63) . '.'
            . str_repeat('c', 63) . '.'
            . str_repeat('d', 63) . '.'
            . str_repeat('e', 63) . '.'
            . str_repeat('f', 63) . '.'
            . str_repeat('g', 63) . '.'
            . str_repeat('h', 51);
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
