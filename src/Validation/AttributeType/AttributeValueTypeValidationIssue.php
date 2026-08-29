<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

/** One error or warning reported by an attribute-type validator. */
final class AttributeValueTypeValidationIssue
{
    public const ERROR = 'error';
    public const WARNING = 'warning';

    public function __construct(
        public readonly string $code,
        public readonly string $level,
        public readonly string $message,
    ) {
        if ($code === '' || $message === '') {
            throw new \InvalidArgumentException('Attribute value validation issue code and message cannot be empty.');
        }
        if (!in_array($level, [self::ERROR, self::WARNING], true)) {
            throw new \InvalidArgumentException('Attribute value validation issue level must be "error" or "warning".');
        }
    }

    public function isError(): bool
    {
        return $this->level === self::ERROR;
    }
}
