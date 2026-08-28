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
            new AttributeValue('any', ['first', 'second']),
        ]));

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
        self::assertSame([], $result->errors());
        self::assertSame([], $result->warnings());
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
