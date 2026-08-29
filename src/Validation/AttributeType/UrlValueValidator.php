<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\CategoryProductValidationIssue;

final class UrlValueValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 1024;

    public function type(): string
    {
        return AttributeType::URL;
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, AttributeType::URL);
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }

        $valid = false;
        if (filter_var($context->value, FILTER_VALIDATE_URL) !== false) {
            $scheme = parse_url($context->value, PHP_URL_SCHEME);
            if ($scheme === 'http' || $scheme === 'https') {
                $valid = true;
            }
        }

        if (!$valid) {
            $issues[] = new AttributeValueTypeValidationIssue(
                CategoryProductValidationIssue::ATTRIBUTE_TYPE_INVALID,
                AttributeValueTypeValidationIssue::ERROR,
                'Value must be an absolute HTTP or HTTPS URL.',
            );
        }

        return new AttributeValueTypeValidationResult($issues);
    }
}
