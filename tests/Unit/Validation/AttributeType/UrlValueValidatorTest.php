<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\AttributeType\UrlValueValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlValueValidatorTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsHttpUrls(string $value): void
    {
        self::assertTrue((new UrlValueValidator())->isValid($value));
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidOrNonHttpUrls(string $value): void
    {
        self::assertFalse((new UrlValueValidator())->isValid($value));
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
        yield 'empty' => [''];
    }
}
