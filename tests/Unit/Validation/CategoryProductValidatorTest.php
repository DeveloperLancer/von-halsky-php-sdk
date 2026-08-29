<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeDictionary;
use DevLancer\VonHalsky\Model\Category\AttributeDictionaryOption;
use DevLancer\VonHalsky\Model\Category\AttributeExpectedValue;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationIssue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;
use DevLancer\VonHalsky\Validation\CategoryProductValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use PHPUnit\Framework\TestCase;

final class CategoryProductValidatorTest extends TestCase
{
    public function testRejectsMalformedOrDuplicatedDefinitions(): void
    {
        $categoryId = CategoryId::fromString('category-1');

        try {
            $this->constructWithUncheckedDefinitions(['definition' => $this->definition('attribute-1')]);
            self::fail('Expected a non-list definition collection to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $this->constructWithUncheckedDefinitions(['invalid']);
            self::fail('Expected an invalid definition item to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            new CategoryProductValidator($categoryId, [$this->definition('')]);
            self::fail('Expected an empty definition ID to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        new CategoryProductValidator($categoryId, [
            $this->definition('attribute-1'),
            $this->definition('attribute-1'),
        ]);
    }

    public function testAcceptsKnownCardinalitiesAndOptionalAttributes(): void
    {
        $validator = $this->validator([
            $this->definition('one', AttributeExpectedValue::ONE),
            $this->definition('many', AttributeExpectedValue::AT_LEAST_ONE),
            $this->definition('optional', AttributeExpectedValue::NULL_OR_ONE),
            $this->definition('any', AttributeExpectedValue::ANY),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('one', ['value']),
            new AttributeValue('many', ['first', 'second']),
            new AttributeValue('optional', []),
            new AttributeValue('any', []),
        ]));

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
        self::assertSame([], $result->errors());
        self::assertSame([], $result->warnings());
    }

    public function testEmptyValueListsAreInvalidOnlyForRequiredCardinalities(): void
    {
        $validator = $this->validator([
            $this->definition('optional', AttributeExpectedValue::NULL_OR_ONE),
            $this->definition('any', AttributeExpectedValue::ANY),
            $this->definition('one', AttributeExpectedValue::ONE),
            $this->definition('many', AttributeExpectedValue::AT_LEAST_ONE),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('optional', []),
            new AttributeValue('any', []),
            new AttributeValue('one', []),
            new AttributeValue('many', []),
        ]));

        self::assertSame([
            CategoryProductValidationIssue::ATTRIBUTE_CARDINALITY_INVALID,
            CategoryProductValidationIssue::ATTRIBUTE_CARDINALITY_INVALID,
        ], self::codes($result->errors()));
        self::assertSame('one', $result->errors()[0]->attributeId);
        self::assertSame('many', $result->errors()[1]->attributeId);
    }

    public function testReportsMissingRequiredAttributesAndInvalidCardinality(): void
    {
        $validator = $this->validator([
            $this->definition('missing-one', AttributeExpectedValue::ONE),
            $this->definition('missing-many', AttributeExpectedValue::AT_LEAST_ONE),
            $this->definition('one', AttributeExpectedValue::ONE),
            $this->definition('optional', AttributeExpectedValue::NULL_OR_ONE),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('one', ['first', 'second']),
            new AttributeValue('optional', ['first', 'second']),
        ]));

        self::assertFalse($result->isValid());
        self::assertSame([
            CategoryProductValidationIssue::ATTRIBUTE_CARDINALITY_INVALID,
            CategoryProductValidationIssue::ATTRIBUTE_CARDINALITY_INVALID,
            CategoryProductValidationIssue::REQUIRED_ATTRIBUTE_MISSING,
            CategoryProductValidationIssue::REQUIRED_ATTRIBUTE_MISSING,
        ], self::codes($result->errors()));
        self::assertSame('Product.attributes', $result->errors()[2]->fieldPath);
        self::assertSame('missing-one', $result->errors()[2]->attributeId);
        self::assertSame('Missing-one', $result->errors()[2]->attributeName);
    }

    public function testReportsDuplicateAndUnknownProductAttributes(): void
    {
        $validator = $this->validator([$this->definition('known')]);
        $product = $this->product('category-1', [
            new AttributeValue('known', ['first']),
            new AttributeValue('known', ['second']),
            new AttributeValue('unknown', ['value']),
        ]);
        $result = $validator->validate($product);

        self::assertSame([
            CategoryProductValidationIssue::ATTRIBUTE_DUPLICATED,
            CategoryProductValidationIssue::ATTRIBUTE_UNKNOWN,
        ], self::codes($result->errors()));
    }

    public function testValidatesActiveInactiveAndUnknownDictionaryValues(): void
    {
        $dictionary = new AttributeDictionary('dictionary-1', 'Colours', [
            new AttributeDictionaryOption('green', 'green', true, 'en'),
            new AttributeDictionaryOption('red', 'red', false, 'en'),
        ]);
        $validator = $this->validator([
            $this->definition('colour', AttributeExpectedValue::ANY, AttributeType::DICTIONARY, $dictionary),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('colour', ['green', 'red', 'blue'], 'en'),
        ]));

        self::assertSame([
            CategoryProductValidationIssue::DICTIONARY_VALUE_INACTIVE,
            CategoryProductValidationIssue::DICTIONARY_VALUE_UNKNOWN,
        ], self::codes($result->errors()));
        self::assertSame('Product.attributes[0].values[1]', $result->errors()[0]->fieldPath);
        self::assertSame('Product.attributes[0].values[2]', $result->errors()[1]->fieldPath);
    }

    public function testValidatesKnownValueTypes(): void
    {
        $validator = $this->validator([
            $this->definition('text', AttributeExpectedValue::ANY, AttributeType::TEXT_VALUE),
            $this->definition('long-text', AttributeExpectedValue::ANY, AttributeType::LONG_TEXT_VALUE),
            $this->definition('integer', AttributeExpectedValue::ANY, AttributeType::NUMERIC),
            $this->definition('decimal', AttributeExpectedValue::ANY, AttributeType::NUMERIC_FLOAT),
            $this->definition('date', AttributeExpectedValue::ANY, AttributeType::DATE),
            $this->definition('url', AttributeExpectedValue::ANY, AttributeType::URL),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('text', ['any text']),
            new AttributeValue('long-text', ['longer text']),
            new AttributeValue('integer', ['42']),
            new AttributeValue('decimal', ['3.14']),
            new AttributeValue('date', ['2026-08-28']),
            new AttributeValue('url', ['https://example.com/product']),
        ]));

        self::assertTrue($result->isValid());
    }

    public function testMapsTheCommonAttributeValueLimitWithItsExactPath(): void
    {
        $validator = $this->validator([
            $this->definition('long-text', AttributeExpectedValue::ONE, AttributeType::LONG_TEXT_VALUE),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('long-text', [str_repeat('ą', 1025)]),
        ]));

        self::assertSame([CategoryProductValidationIssue::ATTRIBUTE_VALUE_TOO_LONG], self::codes($result->errors()));
        self::assertSame('Product.attributes[0].values[0]', $result->errors()[0]->fieldPath);
        self::assertSame('long-text', $result->errors()[0]->attributeId);
    }

    public function testReportsEveryValueWithAnInvalidKnownType(): void
    {
        $validator = $this->validator([
            $this->definition('integer', AttributeExpectedValue::ANY, AttributeType::NUMERIC),
            $this->definition('decimal', AttributeExpectedValue::ANY, AttributeType::NUMERIC_FLOAT),
            $this->definition('date', AttributeExpectedValue::ANY, AttributeType::DATE),
            $this->definition('url', AttributeExpectedValue::ANY, AttributeType::URL),
        ]);

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('integer', ['1.5']),
            new AttributeValue('decimal', ['1,5']),
            new AttributeValue('date', ['2026-02-30']),
            new AttributeValue('url', ['ftp://example.com/product']),
        ]));

        self::assertFalse($result->isValid());
        self::assertSame([
            CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
            CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
            CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
            CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
        ], self::codes($result->errors()));
        self::assertSame('Product.attributes[3].values[0]', $result->errors()[3]->fieldPath);
    }

    public function testUnknownDefinitionMetadataProducesNonBlockingWarnings(): void
    {
        $validator = $this->validator([
            $this->definition('future', 'FUTURE_CARDINALITY', 'FUTURE_TYPE'),
            $this->definition('dictionary', AttributeExpectedValue::ANY, AttributeType::DICTIONARY),
        ]);

        $result = $validator->validate($this->product('category-1'));

        self::assertTrue($result->isValid());
        self::assertSame([
            CategoryProductValidationIssue::EXPECTED_VALUE_UNSUPPORTED,
            CategoryProductValidationIssue::ATTRIBUTE_TYPE_UNSUPPORTED,
            CategoryProductValidationIssue::DICTIONARY_MISSING,
        ], self::codes($result->warnings()));
    }

    public function testUsesRegisteredValidatorForApplicationDefinedAttributeType(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults([
            new class implements AttributeValueTypeValidatorInterface {
                public function type(): string
                {
                    return 'APPLICATION_CODE';
                }

                public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
                {
                    if ($context->value === 'accepted') {
                        return AttributeValueTypeValidationResult::valid();
                    }

                    return new AttributeValueTypeValidationResult([
                        new AttributeValueTypeValidationIssue(
                            CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                            AttributeValueTypeValidationIssue::ERROR,
                            'Application code is invalid.',
                        ),
                    ]);
                }
            },
        ]);
        $validator = new CategoryProductValidator(
            CategoryId::fromString('category-1'),
            [$this->definition('application-code', AttributeExpectedValue::ONE, 'APPLICATION_CODE')],
            $registry,
        );

        $invalid = $validator->validate($this->product('category-1', [
            new AttributeValue('application-code', ['invalid']),
        ]));
        $valid = $validator->validate($this->product('category-1', [
            new AttributeValue('application-code', ['accepted']),
        ]));

        self::assertSame([CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID], self::codes($invalid->errors()));
        self::assertSame([], $invalid->warnings());
        self::assertTrue($valid->isValid());
    }

    public function testMapsMultipleApplicationValidatorIssuesWithValueContext(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults([
            new class implements AttributeValueTypeValidatorInterface {
                public function type(): string
                {
                    return 'APPLICATION_CODE';
                }

                public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
                {
                    if ($context->value !== 'second') {
                        return AttributeValueTypeValidationResult::valid();
                    }

                    return new AttributeValueTypeValidationResult([
                        new AttributeValueTypeValidationIssue('application_error', AttributeValueTypeValidationIssue::ERROR, 'Application error.'),
                        new AttributeValueTypeValidationIssue('application_warning', AttributeValueTypeValidationIssue::WARNING, 'Application warning.'),
                    ]);
                }
            },
        ]);
        $validator = new CategoryProductValidator(
            CategoryId::fromString('category-1'),
            [
                $this->definition('text', AttributeExpectedValue::ANY, AttributeType::TEXT_VALUE),
                $this->definition('application-code', AttributeExpectedValue::ANY, 'APPLICATION_CODE'),
            ],
            $registry,
        );

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('text', ['value']),
            new AttributeValue('application-code', ['first', 'second']),
        ]));

        self::assertFalse($result->isValid());
        self::assertSame(['application_error'], self::codes($result->errors()));
        self::assertSame(['application_warning'], self::codes($result->warnings()));
        self::assertSame('Product.attributes[1].values[1]', $result->errors()[0]->fieldPath);
        self::assertSame('application-code', $result->errors()[0]->attributeId);
        self::assertSame('Application-code', $result->errors()[0]->attributeName);
    }

    public function testReportsErrorWhenKnownApiTypeHasNoRegisteredValidator(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults()
            ->remove(AttributeType::NUMERIC);
        $validator = new CategoryProductValidator(
            CategoryId::fromString('category-1'),
            [$this->definition('integer', AttributeExpectedValue::ONE, AttributeType::NUMERIC)],
            $registry,
        );

        $result = $validator->validate($this->product('category-1', [
            new AttributeValue('integer', ['42']),
        ]));

        self::assertFalse($result->isValid());
        self::assertSame([CategoryProductValidationIssue::ATTRIBUTE_TYPE_VALIDATOR_MISSING], self::codes($result->errors()));
        self::assertSame([], $result->warnings());
    }

    public function testCategoryMismatchStopsCategorySpecificChecks(): void
    {
        $validator = $this->validator([
            $this->definition('required', AttributeExpectedValue::ONE),
        ]);

        $result = $validator->validate($this->product('different-category'));

        self::assertFalse($result->isValid());
        self::assertSame([CategoryProductValidationIssue::CATEGORY_MISMATCH], self::codes($result->issues));
    }

    /** @param list<AttributeDefinition> $definitions */
    private function validator(array $definitions): CategoryProductValidator
    {
        return new CategoryProductValidator(CategoryId::fromString('category-1'), $definitions);
    }

    private function definition(
        string $id,
        string $expectedValue = AttributeExpectedValue::ANY,
        string $type = AttributeType::TEXT_VALUE,
        ?AttributeDictionary $dictionary = null,
    ): AttributeDefinition {
        return new AttributeDefinition(
            $id,
            ucfirst($id),
            AttributeType::fromString($type),
            AttributeExpectedValue::fromString($expectedValue),
            null,
            'en',
            $dictionary,
        );
    }

    /** @param list<AttributeValue> $attributes */
    private function product(string $categoryId, array $attributes = []): ProductProposal
    {
        return new ProductProposal(
            'Product name',
            str_repeat('Long product description. ', 5),
            'Brand',
            CategoryId::fromString($categoryId),
            new Ean('5901234123457'),
            attributes: $attributes,
        );
    }

    /**
     * @param list<CategoryProductValidationIssue> $issues
     * @return list<string>
     */
    private static function codes(array $issues): array
    {
        return array_map(static fn (CategoryProductValidationIssue $issue): string => $issue->code, $issues);
    }

    /** @param array<mixed> $definitions */
    private function constructWithUncheckedDefinitions(array $definitions): void
    {
        (new \ReflectionClass(CategoryProductValidator::class))->newInstanceArgs([
            CategoryId::fromString('category-1'),
            $definitions,
        ]);
    }
}
