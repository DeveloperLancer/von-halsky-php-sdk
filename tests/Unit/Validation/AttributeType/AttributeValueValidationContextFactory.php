<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\AttributeDictionary;
use DevLancer\VonHalsky\Model\Category\AttributeExpectedValue;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\ValueObject\CategoryId;

final class AttributeValueValidationContextFactory
{
    public static function create(
        string $type,
        string $value,
        ?AttributeDictionary $dictionary = null,
    ): AttributeValueValidationContext {
        $definition = new AttributeDefinition(
            id: 'attribute-1',
            name: 'Attribute 1',
            type: AttributeType::fromString($type),
            expectedValue: AttributeExpectedValue::fromString(AttributeExpectedValue::ONE),
            description: null,
            language: 'en',
            dictionary: $dictionary,
        );
        $attribute = new AttributeValue('attribute-1', [$value], 'en');

        return new AttributeValueValidationContext(
            CategoryId::fromString('category-1'),
            $definition,
            $attribute,
            0,
            0,
        );
    }
}
