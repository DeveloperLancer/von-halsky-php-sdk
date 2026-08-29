<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\DictionaryValueValidator;
use PHPUnit\Framework\TestCase;

final class DictionaryValueValidatorTest extends TestCase
{
    public function testDefersMembershipValidationToCategoryValidator(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'dictionary value');
        $result = (new DictionaryValueValidator())->validate($context);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
    }

    public function testOwnLimitAccepts1024AndRejects1025Characters(): void
    {
        $atLimit = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, str_repeat('ą', 1024));
        $aboveLimit = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, str_repeat('ą', 1025));
        $validator = new DictionaryValueValidator();

        self::assertTrue($validator->validate($atLimit)->isValid());
        self::assertFalse($validator->validate($aboveLimit)->isValid());
    }
}
