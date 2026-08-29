<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeDictionary;
use DevLancer\VonHalsky\Model\Category\AttributeDictionaryOption;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\DictionaryValueValidator;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use PHPUnit\Framework\TestCase;

final class DictionaryValueValidatorTest extends TestCase
{
    public function testAcceptsAnExactActiveOptionValue(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'Allowed value', self::dictionary());
        $result = (new DictionaryValueValidator())->validate($context);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
    }

    public function testRejectsAnInactiveOptionValue(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'Inactive value', self::dictionary());
        $result = (new DictionaryValueValidator())->validate($context);

        self::assertSame(CategoryProductValidationIssue::DICTIONARY_VALUE_INACTIVE, $result->errors()[0]->code);
    }

    public function testAcceptsAValueWhenAnyMatchingOptionIsActive(): void
    {
        $dictionary = new AttributeDictionary('dictionary-1', 'Dictionary', [
            new AttributeDictionaryOption('inactive-id', 'Repeated value', false, 'en'),
            new AttributeDictionaryOption('active-id', 'Repeated value', true, 'pl'),
        ]);
        $context = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'Repeated value', $dictionary);

        self::assertTrue((new DictionaryValueValidator())->validate($context)->isValid());
    }

    public function testRejectsAnUnknownValueAndDoesNotAcceptAnOptionId(): void
    {
        $validator = new DictionaryValueValidator();
        $unknown = $validator->validate(AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'Unknown value', self::dictionary()));
        $optionId = $validator->validate(AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'active-option-id', self::dictionary()));

        self::assertSame(CategoryProductValidationIssue::DICTIONARY_VALUE_UNKNOWN, $unknown->errors()[0]->code);
        self::assertSame(CategoryProductValidationIssue::DICTIONARY_VALUE_UNKNOWN, $optionId->errors()[0]->code);
    }

    public function testSkipsMembershipWhenTheDefinitionContainsNoDictionary(): void
    {
        $context = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, 'dictionary value');

        self::assertTrue((new DictionaryValueValidator())->validate($context)->isValid());
    }

    public function testOwnLimitAccepts1024AndRejects1025Characters(): void
    {
        $atLimit = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, str_repeat('ą', 1024));
        $aboveLimit = AttributeValueValidationContextFactory::create(AttributeType::DICTIONARY, str_repeat('ą', 1025));
        $validator = new DictionaryValueValidator();

        self::assertTrue($validator->validate($atLimit)->isValid());
        self::assertFalse($validator->validate($aboveLimit)->isValid());
    }

    private static function dictionary(): AttributeDictionary
    {
        return new AttributeDictionary('dictionary-1', 'Dictionary', [
            new AttributeDictionaryOption('active-option-id', 'Allowed value', true, 'en'),
            new AttributeDictionaryOption('inactive-option-id', 'Inactive value', false, 'en'),
        ]);
    }
}
