<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\LongTextValueValidator;
use PHPUnit\Framework\TestCase;

final class LongTextValueValidatorTest extends TestCase
{
    public function testAcceptsLongTextAtItsOwnLimit(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::LONG_TEXT_VALUE, str_repeat('ą', 1024));
        $result = (new LongTextValueValidator())->validate($context);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
    }

    public function testRejectsLongTextAboveItsOwnLimit(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::LONG_TEXT_VALUE, str_repeat('ą', 1025));

        self::assertFalse((new LongTextValueValidator())->validate($context)->isValid());
    }
}
