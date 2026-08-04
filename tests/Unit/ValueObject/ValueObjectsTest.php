<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\ValueObject;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\ValueObject\Currency;
use DevLancer\VonHalsky\ValueObject\Dimensions;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;
use DevLancer\VonHalsky\ValueObject\Weight;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testMoneyNeverAcceptsFloatAndNormalizesDecimal(): void
    {
        $money = Money::fromDecimal('49.9', Currency::PLN);

        self::assertSame('49.90', $money->amount);
        self::assertSame(4990, $money->minorUnits());
    }

    #[DataProvider('invalidMoneyProvider')]
    public function testMoneyRejectsValuesOutsideConfirmedContract(string $amount): void
    {
        $this->expectException(InvalidRequestException::class);
        Money::fromDecimal($amount);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidMoneyProvider(): iterable
    {
        yield 'below minimum' => ['0'];
        yield 'too precise' => ['0.001'];
        yield 'above maximum' => ['1000000.00'];
        yield 'scientific notation' => ['1e2'];
    }

    public function testDimensionAndWeightBoundariesAreInclusive(): void
    {
        $dimensions = new Dimensions(1, 10000, 50);
        $weight = new Weight(1000000);

        self::assertSame(1, $dimensions->width);
        self::assertSame(1000000, $weight->grams);
    }

    #[DataProvider('invalidMeasurementProvider')]
    public function testMeasurementsRejectValuesOutsideConfirmedContract(
        int $width,
        int $height,
        int $length,
        int $weight,
    ): void {
        $this->expectException(InvalidRequestException::class);
        new Dimensions($width, $height, $length);
        new Weight($weight);
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function invalidMeasurementProvider(): iterable
    {
        yield 'width below minimum' => [0, 1, 1, 1];
        yield 'height above maximum' => [1, 10001, 1, 1];
        yield 'length below minimum' => [1, 1, 0, 1];
    }

    #[DataProvider('invalidWeightProvider')]
    public function testWeightRejectsValuesOutsideConfirmedContract(int $weight): void
    {
        $this->expectException(InvalidRequestException::class);
        new Weight($weight);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidWeightProvider(): iterable
    {
        yield 'below minimum' => [0];
        yield 'above maximum' => [1000001];
    }

    public function testIdentifierEanAndUtcDateAreTyped(): void
    {
        self::assertSame('offer-1', (string) OfferId::fromString('offer-1'));
        self::assertSame('05901234123457', (string) new Ean('05901234123457'));
        self::assertSame(
            '2026-08-04T20:15:00+00:00',
            UtcDateTime::fromString('2026-08-04T22:15:00+02:00')->toAtomString(),
        );
        self::assertSame(
            '2026-08-04T20:15:00+00:00',
            UtcDateTime::from(new DateTimeImmutable('2026-08-04T20:15:00Z'))->toAtomString(),
        );
    }

    public function testUtcDateRequiresAnExplicitIsoOffset(): void
    {
        $this->expectException(InvalidRequestException::class);
        UtcDateTime::fromString('tomorrow +00:00');
    }
}
