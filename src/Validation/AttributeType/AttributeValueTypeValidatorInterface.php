<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

/** One pluggable validator for values of a category attribute type. */
interface AttributeValueTypeValidatorInterface
{
    /** The API attribute-type identifier handled by this validator. */
    public function type(): string;

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult;
}
