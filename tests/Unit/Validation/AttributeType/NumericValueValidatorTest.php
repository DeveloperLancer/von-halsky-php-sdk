<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\NumericValueValidator;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumericValueValidatorTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsIntegerFormat(string $value): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::NUMERIC, $value);

        self::assertTrue((new NumericValueValidator())->validate($context)->isValid());
    }

    #[DataProvider('invalidValues')]
    public function testRejectsNonIntegerFormat(string $value): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::NUMERIC, $value);

        self::assertFalse((new NumericValueValidator())->validate($context)->isValid());
    }

    public function testRejectsIntegerAboveItsOwnLimit(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::NUMERIC, str_repeat('1', 1025));
        $result = (new NumericValueValidator())->validate($context);

        self::assertSame(CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG, $result->errors()[0]->code);
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'zero' => ['0'];
        yield 'positive' => ['42'];
        yield 'large integer' => ['2147483648'];
        yield 'own length limit' => [str_repeat('1', 1024)];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'decimal point' => ['42.0'];
        yield 'decimal comma' => ['42,0'];
        yield 'explicit positive sign' => ['+42'];
        yield 'negative sign' => ['-42'];
        yield 'alphabetic' => ['forty-two'];
        yield 'whitespace' => [' 42'];
    }
}
