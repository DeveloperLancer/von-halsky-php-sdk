<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestValidatorTest extends TestCase
{
    #[DataProvider('collectionLimitProvider')]
    public function testConfirmedCollectionMaximums(int $maximum, string $kind): void
    {
        self::validateCollection($kind, self::collection($kind, $maximum));
        self::addToAssertionCount(1);

        $this->expectException(InvalidRequestException::class);
        self::validateCollection($kind, self::collection($kind, $maximum + 1));
    }

    /** @return iterable<string, array{int, string}> */
    public static function collectionLimitProvider(): iterable
    {
        yield 'images' => [20, 'images'];
        yield 'manuals' => [20, 'manuals'];
        yield 'attributes' => [120, 'attributes'];
        yield 'batch' => [500, 'batch'];
    }

    public function testShippingTimeBoundaries(): void
    {
        RequestValidator::daysToShip(0);
        RequestValidator::daysToShip(60);
        self::addToAssertionCount(2);
    }

    #[DataProvider('invalidShippingTimeProvider')]
    public function testShippingTimeRejectsOutsideBoundaries(int $days): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::daysToShip($days);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidShippingTimeProvider(): iterable
    {
        yield 'below minimum' => [-1];
        yield 'above maximum' => [61];
    }

    public function testAttributeValuesAcceptAnEmptyListEmptyTextAndTheOfficialMaximum(): void
    {
        RequestValidator::attributeValues([], 'Offer.attributes.values', 1024);
        RequestValidator::attributeValues(['', str_repeat('ą', 1024)], 'Offer.attributes.values', 1024);

        self::addToAssertionCount(2);
    }

    public function testAttributeValuesRejectTextAboveTheOfficialMaximum(): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::attributeValues([str_repeat('ą', 1025)], 'Offer.attributes.values', 1024);
    }

    public function testAttributeValuesMustBeAList(): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::attributeValues(['value' => 'text'], 'Product.attributes.values');
    }

    public function testAttributeValueItemsMustBeStrings(): void
    {
        $this->expectException(InvalidRequestException::class);
        RequestValidator::attributeValues([123], 'Product.attributes.values');
    }

    /** @return list<mixed> */
    private static function collection(string $kind, int $count): array
    {
        return match ($kind) {
            'images' => array_fill(0, $count, new OfferImage('product.webp', 'https://example.com/product.webp', 1)),
            'manuals' => array_fill(0, $count, ['title' => 'Manual title', 'url' => 'https://example.com/manual.pdf']),
            'attributes' => array_fill(0, $count, new AttributeValue('attribute-1', ['value'])),
            'batch' => array_fill(0, $count, self::createOfferRequest()),
            default => throw new \LogicException('Unknown validation kind.'),
        };
    }

    /** @param list<mixed> $items */
    private static function validateCollection(string $kind, array $items): void
    {
        switch ($kind) {
            case 'images':
                RequestValidator::offerImages($items);
                break;
            case 'manuals':
                RequestValidator::gpsrManuals($items);
                break;
            case 'attributes':
                RequestValidator::productAttributes($items);
                break;
            case 'batch':
                RequestValidator::offerBatch($items);
                break;
            default:
                throw new \LogicException('Unknown validation kind.');
        }
    }

    private static function createOfferRequest(): CreateOfferRequest
    {
        return new CreateOfferRequest(
            new ProductProposal('Product', str_repeat('D', 100), 'Brand', CategoryId::fromString('category-1'), new Ean('5901234123457')),
            new Stock(1),
            new Price(Money::fromDecimal('1.00'), '23%'),
            images: [new OfferImage('product.webp', 'https://example.com/product.webp', 1)],
        );
    }
}
