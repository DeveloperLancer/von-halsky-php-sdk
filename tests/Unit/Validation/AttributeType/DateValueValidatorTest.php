<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\AttributeType\DateValueValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateValueValidatorTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsIsoCalendarDate(string $value): void
    {
        self::assertTrue((new DateValueValidator())->isValid($value));
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidOrNonIsoDate(string $value): void
    {
        self::assertFalse((new DateValueValidator())->isValid($value));
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'ordinary date' => ['2026-08-29'];
        yield 'leap day' => ['2024-02-29'];
        yield 'minimum month and day' => ['2026-01-01'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'non-leap day' => ['2026-02-29'];
        yield 'invalid day' => ['2026-04-31'];
        yield 'missing zero padding' => ['2026-8-9'];
        yield 'different separator' => ['2026/08/29'];
        yield 'date time' => ['2026-08-29T12:00:00Z'];
        yield 'empty' => [''];
    }
}
