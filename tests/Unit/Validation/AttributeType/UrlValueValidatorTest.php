<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\UrlValueValidator;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
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

    public function testRejectsUrlAboveItsOwnLimit(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::URL, 'https://example.com/' . str_repeat('a', 1005));
        $result = (new UrlValueValidator())->validate($context);

        self::assertContains(CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG, array_column($result->errors(), 'code'));
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'https' => ['https://example.com/product?id=42'];
        yield 'http' => ['http://example.com/image.jpg'];
        yield 'own length limit' => ['https://example.com/' . str_repeat('a', 1004)];
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
