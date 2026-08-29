<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\UrlValueValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlValueValidatorTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsHttpUrls(string $value): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::URL, $value);

        self::assertTrue((new UrlValueValidator())->validate($context)->isValid());
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidOrNonHttpUrls(string $value): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::URL, $value);

        self::assertFalse((new UrlValueValidator())->validate($context)->isValid());
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'https' => ['https://example.com/product?id=42'];
        yield 'http' => ['http://example.com/image.jpg'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'ftp protocol' => ['ftp://example.com/file'];
        yield 'missing scheme' => ['example.com/product'];
        yield 'malformed host' => ['https://'];
        yield 'whitespace' => ['https://example.com/a path'];
    }
}
