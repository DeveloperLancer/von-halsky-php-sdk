<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation\AttributeType;

/** All findings reported while validating one attribute value. */
final class AttributeValueTypeValidationResult
{
    /** @var list<AttributeValueTypeValidationIssue> */
    public readonly array $issues;

    /** @param array<mixed> $issues */
    public function __construct(array $issues = [])
    {
        $this->issues = self::validatedIssues($issues);
    }

    /**
     * @param array<mixed> $issues
     * @return list<AttributeValueTypeValidationIssue>
     */
    private static function validatedIssues(array $issues): array
    {
        if (!array_is_list($issues)) {
            throw new \InvalidArgumentException('Attribute value validation issues must be a list.');
        }
        $result = [];
        foreach ($issues as $issue) {
            if (!$issue instanceof AttributeValueTypeValidationIssue) {
                throw new \InvalidArgumentException('Attribute value validation issues must contain only AttributeValueTypeValidationIssue objects.');
            }
            $result[] = $issue;
        }

        return $result;
    }

    public static function valid(): self
    {
        return new self();
    }

    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    /** @return list<AttributeValueTypeValidationIssue> */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (AttributeValueTypeValidationIssue $issue): bool => $issue->isError(),
        ));
    }

    /** @return list<AttributeValueTypeValidationIssue> */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (AttributeValueTypeValidationIssue $issue): bool => !$issue->isError(),
        ));
    }
}
