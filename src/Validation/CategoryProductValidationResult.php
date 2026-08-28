<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Validation;

/** All category-specific validation findings for one product proposal. */
final class CategoryProductValidationResult
{
    /** @var list<CategoryProductValidationIssue> */
    public readonly array $issues;

    /** @param list<CategoryProductValidationIssue> $issues */
    public function __construct(array $issues)
    {
        $this->issues = self::validatedIssues($issues);
    }

    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    /** @return list<CategoryProductValidationIssue> */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (CategoryProductValidationIssue $issue): bool => $issue->isError(),
        ));
    }

    /** @return list<CategoryProductValidationIssue> */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (CategoryProductValidationIssue $issue): bool => !$issue->isError(),
        ));
    }

    /**
     * @param array<mixed> $issues
     * @return list<CategoryProductValidationIssue>
     */
    private static function validatedIssues(array $issues): array
    {
        if (!array_is_list($issues)) {
            throw new \InvalidArgumentException('Validation issues must be a list.');
        }

        $result = [];
        foreach ($issues as $issue) {
            if (!$issue instanceof CategoryProductValidationIssue) {
                throw new \InvalidArgumentException('Validation issues must contain only CategoryProductValidationIssue objects.');
            }
            $result[] = $issue;
        }

        return $result;
    }
}
