<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** Immutable context for validating one value of one category attribute. */
final class AttributeValueValidationContext
{
    public readonly string $value;
    public readonly string $fieldPath;

    public function __construct(
        public readonly CategoryId $categoryId,
        public readonly AttributeDefinition $definition,
        public readonly AttributeValue $attribute,
        public readonly int $attributeIndex,
        public readonly int $valueIndex,
    ) {
        if ($attributeIndex < 0 || $valueIndex < 0) {
            throw new \InvalidArgumentException('Attribute and value indexes must not be negative.');
        }
        if ($definition->id !== $attribute->id) {
            throw new \InvalidArgumentException('Attribute definition ID must match the validated attribute ID.');
        }
        if (!array_key_exists($valueIndex, $attribute->values)) {
            throw new \InvalidArgumentException('The validated value index does not exist in the attribute values.');
        }

        $this->value = $attribute->values[$valueIndex];
        $this->fieldPath = sprintf('Product.attributes[%d].values[%d]', $attributeIndex, $valueIndex);
    }
}
