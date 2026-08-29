<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeExpectedValue;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttributeValueValidationContextTest extends TestCase
{
    public function testDerivesValueAndFieldPath(): void
    {
        $context = new AttributeValueValidationContext(
            CategoryId::fromString('category-1'),
            $this->definition('attribute-1'),
            new AttributeValue('attribute-1', ['first', 'second']),
            3,
            1,
        );

        self::assertSame('second', $context->value);
        self::assertSame('Product.attributes[3].values[1]', $context->fieldPath);
    }

    #[DataProvider('invalidIndexes')]
    public function testRejectsInvalidIndexes(int $attributeIndex, int $valueIndex): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AttributeValueValidationContext(
            CategoryId::fromString('category-1'),
            $this->definition('attribute-1'),
            new AttributeValue('attribute-1', ['value']),
            $attributeIndex,
            $valueIndex,
        );
    }

    public function testRejectsMismatchedDefinitionAndAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AttributeValueValidationContext(
            CategoryId::fromString('category-1'),
            $this->definition('definition-1'),
            new AttributeValue('attribute-1', ['value']),
            0,
            0,
        );
    }

    /** @return iterable<string, array{int, int}> */
    public static function invalidIndexes(): iterable
    {
        yield 'negative attribute index' => [-1, 0];
        yield 'negative value index' => [0, -1];
        yield 'missing value index' => [0, 1];
    }

    private function definition(string $id): AttributeDefinition
    {
        return new AttributeDefinition(
            $id,
            'Attribute',
            AttributeType::fromString(AttributeType::TEXT_VALUE),
            AttributeExpectedValue::fromString(AttributeExpectedValue::ONE),
            null,
            'en',
            null,
        );
    }
}
