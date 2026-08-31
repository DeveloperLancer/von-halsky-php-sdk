<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use DevLancer\VonHalsky\Validation\CategoryProductValidationResult;
use PHPUnit\Framework\TestCase;

final class CategoryProductValidationResultTest extends TestCase
{
    public function testSeparatesErrorsAndWarnings(): void
    {
        $error = new CategoryProductValidationIssue('error_code', CategoryProductValidationIssue::ERROR, 'Error message.', 'Product.attributes');
        $warning = new CategoryProductValidationIssue('warning_code', CategoryProductValidationIssue::WARNING, 'Warning message.', 'Product.attributes');
        $result = new CategoryProductValidationResult([$error, $warning]);

        self::assertFalse($result->isValid());
        self::assertSame([$error], $result->errors());
        self::assertSame([$warning], $result->warnings());
    }

    public function testRejectsMalformedIssueCollections(): void
    {
        $issue = new CategoryProductValidationIssue('code', CategoryProductValidationIssue::ERROR, 'Message.', 'Product.attributes');

        $this->expectException(\InvalidArgumentException::class);
        self::constructWithInvalidArguments(CategoryProductValidationResult::class, [['issue' => $issue]]);
    }

    public function testRejectsNonIssueItemsAndInvalidIssueMetadata(): void
    {
        try {
            self::constructWithInvalidArguments(CategoryProductValidationResult::class, [[new \stdClass()]]);
            self::fail('Expected a non-issue item to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        new CategoryProductValidationIssue('', 'invalid', '', '');
    }

    /**
     * @param class-string $class
     * @param array<int, mixed> $arguments
     */
    private static function constructWithInvalidArguments(string $class, array $arguments): object
    {
        return (new \ReflectionClass($class))->newInstanceArgs($arguments);
    }
}
