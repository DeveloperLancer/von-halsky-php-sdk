<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class UrlValueValidator implements AttributeValueTypeValidatorInterface
{
    public function type(): string
    {
        return AttributeType::URL;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        if (filter_var($context->value, FILTER_VALIDATE_URL) !== false) {
            $scheme = parse_url($context->value, PHP_URL_SCHEME);
            if ($scheme === 'http' || $scheme === 'https') {
                return AttributeValueTypeValidationResult::valid();
            }
        }

        return new AttributeValueTypeValidationResult([
            new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must be an absolute HTTP or HTTPS URL.',
            ),
        ]);
    }
}
