<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestValidatorTest extends TestCase
{
    /** @param list<int> $values */
    #[DataProvider('collectionLimitProvider')]
    public function testConfirmedCollectionMaximums(array $values, int $maximum, string $kind): void
    {
        self::validateCollection($kind, array_slice($values, 0, $maximum));
        self::addToAssertionCount(1);

        $this->expectException(InvalidRequestException::class);
        self::validateCollection($kind, $values);
    }

    /** @return iterable<string, array{list<int>, int, string}> */
    public static function collectionLimitProvider(): iterable
    {
        yield 'images' => [range(1, 21), 20, 'images'];
        yield 'manuals' => [range(1, 21), 20, 'manuals'];
        yield 'attributes' => [range(1, 121), 120, 'attributes'];
        yield 'batch' => [range(1, 501), 500, 'batch'];
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

    /** @param list<int> $items */
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
}
