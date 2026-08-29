<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueItemValidator;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use PHPUnit\Framework\TestCase;

final class AttributeValueItemValidatorTest extends TestCase
{
    public function testAcceptsTheOfficialCommonMaximum(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::LONG_TEXT_VALUE, str_repeat('ą', 1024));

        self::assertTrue((new AttributeValueItemValidator())->validate($context)->isValid());
    }

    public function testRejectsAValueAboveTheOfficialCommonMaximum(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::LONG_TEXT_VALUE, str_repeat('ą', 1025));
        $result = (new AttributeValueItemValidator())->validate($context);

        self::assertFalse($result->isValid());
        self::assertSame(CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG, $result->errors()[0]->code);
    }
}
