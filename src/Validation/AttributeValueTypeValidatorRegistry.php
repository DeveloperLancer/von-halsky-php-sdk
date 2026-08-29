<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeType\DateValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\DictionaryValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\LongTextValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\NumericFloatValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\NumericValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\TextValueValidator;
use DevLancer\VonHalsky\Validation\AttributeType\UrlValueValidator;

/** Registry of built-in and application-provided attribute-type validators. */
final class AttributeValueTypeValidatorRegistry
{
    /** @var array<string, AttributeValueTypeValidatorInterface> */
    private array $validators = [];

    /** @param iterable<mixed> $validators */
    public function __construct(iterable $validators)
    {
        foreach ($validators as $validator) {
            if (!$validator instanceof AttributeValueTypeValidatorInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Attribute-type validators must implement %s; %s given.',
                    AttributeValueTypeValidatorInterface::class,
                    get_debug_type($validator),
                ));
            }
            $this->add($validator);
        }
    }

    /**
     * @param iterable<AttributeValueTypeValidatorInterface> $additionalValidators
     */
    public static function withDefaults(iterable $additionalValidators = []): self
    {
        $additional = is_array($additionalValidators)
            ? $additionalValidators
            : iterator_to_array($additionalValidators, false);

        return new self([
            new TextValueValidator(),
            new LongTextValueValidator(),
            new DictionaryValueValidator(),
            new NumericValueValidator(),
            new NumericFloatValueValidator(),
            new DateValueValidator(),
            new UrlValueValidator(),
            ...$additional,
        ]);
    }

    public function supports(AttributeType $type): bool
    {
        return isset($this->validators[$type->value]);
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $type = $context->definition->type->value;
        $validator = $this->validators[$type] ?? null;
        if ($validator === null) {
            throw new \LogicException(sprintf('No attribute value validator is registered for type "%s".', $type));
        }

        return $validator->validate($context);
    }

    public function add(AttributeValueTypeValidatorInterface $validator): self
    {
        $type = $validator->type();
        if ($type === '') {
            throw new \InvalidArgumentException('An attribute-type validator must declare a non-empty type.');
        }
        if (isset($this->validators[$type])) {
            throw new \InvalidArgumentException(sprintf('An attribute-type validator is already registered for type "%s".', $type));
        }

        $this->validators[$type] = $validator;

        return $this;
    }

    public function remove(string $type): self
    {
        unset($this->validators[$type]);

        return $this;
    }
}
