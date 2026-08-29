<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\AttributeType\NumericFloatValueValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumericFloatValueValidatorTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsDotDecimalFormat(string $value): void
    {
        self::assertTrue((new NumericFloatValueValidator())->isValid($value));
    }

    #[DataProvider('invalidValues')]
    public function testRejectsNonDotDecimalFormat(string $value): void
    {
        self::assertFalse((new NumericFloatValueValidator())->isValid($value));
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'integer' => ['42'];
        yield 'dot decimal' => ['42.5'];
        yield 'fraction without leading zero' => ['.5'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'decimal comma' => ['42,5'];
        yield 'explicit positive sign' => ['+42.5'];
        yield 'negative sign' => ['-42.5'];
        yield 'trailing decimal point' => ['42.'];
        yield 'scientific notation' => ['1e3'];
        yield 'alphabetic' => ['forty-two'];
        yield 'empty' => [''];
    }
}
