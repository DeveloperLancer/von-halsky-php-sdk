<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\TextValueValidator;
use PHPUnit\Framework\TestCase;

final class TextValueValidatorTest extends TestCase
{
    public function testAcceptsEmptyTextBecauseTheContractDefinesNoMinimumLength(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::TEXT_VALUE, '');

        self::assertTrue((new TextValueValidator())->validate($context)->isValid());
    }

    public function testAddsNoRuleBeyondTheCommonAttributeValueItemConstraint(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::TEXT_VALUE, str_repeat('ą', 1024));

        self::assertTrue((new TextValueValidator())->validate($context)->isValid());
    }
}
