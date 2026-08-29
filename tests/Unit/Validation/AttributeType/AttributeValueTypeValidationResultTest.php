<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationIssue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use PHPUnit\Framework\TestCase;

final class AttributeValueTypeValidationResultTest extends TestCase
{
    public function testSeparatesErrorsAndWarnings(): void
    {
        $error = new AttributeValueTypeValidationIssue('custom_error', AttributeValueTypeValidationIssue::ERROR, 'Error message.');
        $warning = new AttributeValueTypeValidationIssue('custom_warning', AttributeValueTypeValidationIssue::WARNING, 'Warning message.');
        $result = new AttributeValueTypeValidationResult([$error, $warning]);

        self::assertFalse($result->isValid());
        self::assertSame([$error], $result->errors());
        self::assertSame([$warning], $result->warnings());
    }

    public function testWarningsDoNotInvalidateResult(): void
    {
        $warning = new AttributeValueTypeValidationIssue('custom_warning', AttributeValueTypeValidationIssue::WARNING, 'Warning message.');
        $result = new AttributeValueTypeValidationResult([$warning]);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->errors());
    }

    public function testValidFactoryCreatesEmptyValidResult(): void
    {
        $result = AttributeValueTypeValidationResult::valid();

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
    }

    public function testRejectsMalformedIssueCollections(): void
    {
        try {
            new AttributeValueTypeValidationResult(['issue' => new AttributeValueTypeValidationIssue('code', AttributeValueTypeValidationIssue::ERROR, 'Message.')]);
            self::fail('Expected a non-list issue collection to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        new AttributeValueTypeValidationResult([new \stdClass()]);
    }
}
