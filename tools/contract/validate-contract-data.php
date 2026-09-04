<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

contractRun(static function (): void {
    [$directory] = contractArguments(
        contractCliArguments(),
        1,
        'php tools/contract/validate-contract-data.php <resources/contract>'
    );

    $errors = validateContractDirectory(rtrim($directory, '/\\'));
    if ($errors !== []) {
        foreach ($errors as $error) {
            fwrite(STDERR, '- ' . $error . PHP_EOL);
        }
        throw new RuntimeException(sprintf('Contract data validation failed with %d error(s).', count($errors)));
    }

    fwrite(STDOUT, sprintf("Contract data in %s is valid.%s", $directory, PHP_EOL));
});

/** @return list<string> */
function validateContractDirectory(string $directory): array
{
    $errors = [];
    $requiredFiles = [
        'contract-lock.json',
        'formal-decisions.json',
        'implementation-coverage.json',
        'operations.json',
        'pending-stage-verifications.json',
        'prod-next-diff.json',
        'validation-rules.json',
    ];

    foreach ($requiredFiles as $file) {
        if (!is_file($directory . DIRECTORY_SEPARATOR . $file)) {
            $errors[] = sprintf('Missing required file %s.', $file);
        }
    }
    if ($errors !== []) {
        return $errors;
    }

    try {
        $lock = contractReadJson($directory . DIRECTORY_SEPARATOR . 'contract-lock.json');
        $formal = contractReadJson($directory . DIRECTORY_SEPARATOR . 'formal-decisions.json');
        $coverage = contractReadJson($directory . DIRECTORY_SEPARATOR . 'implementation-coverage.json');
        $manifest = contractReadJson($directory . DIRECTORY_SEPARATOR . 'operations.json');
        $pending = contractReadJson($directory . DIRECTORY_SEPARATOR . 'pending-stage-verifications.json');
        $diff = contractReadJson($directory . DIRECTORY_SEPARATOR . 'prod-next-diff.json');
        $validationRules = contractReadJson($directory . DIRECTORY_SEPARATOR . 'validation-rules.json');
    } catch (Throwable $throwable) {
        return [$throwable->getMessage()];
    }

    validateLock($lock, $errors);
    validateFormalDecisions($formal, $errors);
    validateManifest($manifest, $lock, $errors);
    validateImplementationCoverage($coverage, $manifest, $errors);
    validateDiff($diff, $lock, $errors);
    validateRules($validationRules, $errors);
    validatePendingStage($pending, $errors);
    validateNoSecrets([$lock, $formal, $coverage, $manifest, $pending, $diff, $validationRules], $errors);

    $matchedOpenApiFiles = glob($directory . DIRECTORY_SEPARATOR . '*openapi*.json');
    $openApiFiles = $matchedOpenApiFiles === false ? [] : $matchedOpenApiFiles;
    if ($openApiFiles !== []) {
        $errors[] = 'Full OpenAPI documents must remain under ignored var/contract.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $coverage
 * @param array<string, mixed> $manifest
 * @param list<string>         $errors
 */
function validateImplementationCoverage(array $coverage, array $manifest, array &$errors): void
{
    $implemented = $coverage['implementedOperations'] ?? null;
    $operations = $manifest['operations'] ?? null;
    if (!is_array($implemented) || !is_array($operations)) {
        $errors[] = 'implementation-coverage must contain an implemented operation list.';
        return;
    }

    $known = [];
    foreach ($operations as $operation) {
        if (is_array($operation) && is_string($operation['operationId'] ?? null)) {
            $known[$operation['operationId']] = true;
        }
    }
    $seen = [];
    foreach ($implemented as $index => $operation) {
        if (!is_array($operation)
            || !is_string($operation['operationId'] ?? null)
            || !is_string($operation['publicMethod'] ?? null)
        ) {
            $errors[] = sprintf('implementation-coverage.implementedOperations[%d] is invalid.', $index);
            continue;
        }
        $id = $operation['operationId'];
        if (!isset($known[$id]) || isset($seen[$id])) {
            $errors[] = sprintf('implementation coverage contains unknown or duplicate operation %s.', $id);
        }
        $seen[$id] = true;
    }

    $summary = contractOptionalObject($coverage, 'summary');
    if (($summary['implemented'] ?? null) !== count($implemented)
        || ($summary['total'] ?? null) !== count($operations)
    ) {
        $errors[] = 'implementation coverage summary does not match the operation lists.';
    }
}

/**
 * @param array<string, mixed> $document
 * @param list<string> $errors
 */
function validateFormalDecisions(array $document, array &$errors): void
{
    $decisions = $document['decisions'] ?? null;
    if (!is_array($decisions) || count($decisions) < 5) {
        $errors[] = 'formal-decisions.decisions must cover package, license, trademark, redistribution, and source isolation.';
        return;
    }

    foreach ($decisions as $index => $decision) {
        if (!is_array($decision)) {
            $errors[] = sprintf('formal-decisions.decisions[%d] must be an object.', $index);
            continue;
        }
        foreach (['id', 'decision', 'owner', 'status'] as $field) {
            if (!is_string($decision[$field] ?? null) || $decision[$field] === '') {
                $errors[] = sprintf('formal-decisions.decisions[%d].%s must be non-empty.', $index, $field);
            }
        }
    }
}

/**
 * @param array<string, mixed> $lock
 * @param list<string> $errors
 */
function validateLock(array $lock, array &$errors): void
{
    foreach (['productionSha256', 'nextSha256'] as $field) {
        if (!is_string($lock[$field] ?? null) || preg_match('/^[a-f0-9]{64}$/', $lock[$field]) !== 1) {
            $errors[] = sprintf('contract-lock.%s must be a lowercase SHA-256.', $field);
        }
    }
    foreach (['checkedAt', 'openApiVersion', 'productionContractVersion', 'nextContractVersion'] as $field) {
        if (!is_string($lock[$field] ?? null) || $lock[$field] === '') {
            $errors[] = sprintf('contract-lock.%s must be a non-empty string.', $field);
        }
    }

    $sources = $lock['sources'] ?? null;
    if (!is_array($sources) || count($sources) < 8) {
        $errors[] = 'contract-lock.sources must contain all official source categories.';
        return;
    }
    foreach ($sources as $index => $source) {
        $url = is_array($source) ? ($source['url'] ?? null) : null;
        if (!is_string($url) || !str_starts_with($url, 'https://inpsa-api-portal.inpost-group.com/')) {
            $errors[] = sprintf('contract-lock.sources[%d] is not an official portal URL.', $index);
        }
    }

    $redistribution = contractOptionalObject($lock, 'redistribution');
    if (($redistribution['fullOpenApiCommitted'] ?? null) !== false) {
        $errors[] = 'contract-lock must prohibit committing the full OpenAPI while redistribution is unconfirmed.';
    }
}

/**
 * @param array<string, mixed> $manifest
 * @param array<string, mixed> $lock
 * @param list<string> $errors
 */
function validateManifest(array $manifest, array $lock, array &$errors): void
{
    $operations = $manifest['operations'] ?? null;
    if (!is_array($operations) || count($operations) !== 43) {
        $errors[] = 'operations.json must contain exactly 43 operations.';
        return;
    }
    if (($manifest['generatedFromSha256'] ?? null) !== ($lock['productionSha256'] ?? null)) {
        $errors[] = 'operations.json was not generated from the locked production contract.';
    }

    $ids = [];
    $methodPaths = [];
    $paths = [];
    $phaseCounts = [];
    $scopeCounts = [];
    $deprecated = 0;

    foreach ($operations as $index => $operation) {
        if (!is_array($operation)) {
            $errors[] = sprintf('operations[%d] must be an object.', $index);
            continue;
        }
        $id = $operation['operationId'] ?? null;
        $method = $operation['method'] ?? null;
        $path = $operation['path'] ?? null;
        $phase = $operation['phase'] ?? null;

        if (!is_string($id) || $id === '' || isset($ids[$id])) {
            $errors[] = sprintf('operations[%d] has a missing or duplicate operationId.', $index);
        } else {
            $ids[$id] = true;
        }
        if (!is_string($method) || !is_string($path)) {
            $errors[] = sprintf('operations[%d] has an invalid method/path.', $index);
        } else {
            $methodPath = $method . ' ' . $path;
            if (isset($methodPaths[$methodPath])) {
                $errors[] = sprintf('Duplicate method/path %s.', $methodPath);
            }
            $methodPaths[$methodPath] = true;
            $paths[$path] = true;
        }
        if (!in_array($phase, [5, 6, 7], true)) {
            $errors[] = sprintf('operations[%d] has an invalid phase.', $index);
        } else {
            $phaseCounts[$phase] = ($phaseCounts[$phase] ?? 0) + 1;
        }
        if (!is_string($operation['domain'] ?? null) || $operation['domain'] === '') {
            $errors[] = sprintf('operations[%d] has no domain.', $index);
        }
        $scope = $operation['scope'] ?? null;
        $scopes = $operation['scopes'] ?? null;
        if (!array_key_exists('scope', $operation) || !is_array($scopes)) {
            $errors[] = sprintf('operations[%d] must explicitly declare scope information.', $index);
        } elseif ($scopes === []) {
            if ($scope !== null) {
                $errors[] = sprintf('operations[%d] has an authentication-only scope mismatch.', $index);
            }
        } elseif (!is_string($scope) || $scope === '' || $scopes !== [$scope]) {
            $errors[] = sprintf('operations[%d] must declare one documented OAuth scope.', $index);
        } else {
            $scopeCounts[$scope] = ($scopeCounts[$scope] ?? 0) + 1;
        }
        if (($operation['deprecated'] ?? false) === true) {
            ++$deprecated;
            if (!is_string($operation['alternative'] ?? null) && !is_string($operation['deprecationReason'] ?? null)) {
                $errors[] = sprintf('Deprecated operations[%d] needs an alternative or reason.', $index);
            }
        }
    }

    if (count($paths) !== 39) {
        $errors[] = 'operations.json must describe exactly 39 unique paths.';
    }
    if ($phaseCounts !== [5 => 4, 6 => 19, 7 => 20]) {
        $errors[] = 'operations.json phase allocation must be 4 + 19 + 20.';
    }
    if ($deprecated !== 2) {
        $errors[] = 'operations.json must contain exactly two deprecated operations.';
    }
    ksort($scopeCounts, SORT_STRING);
    if ($scopeCounts !== [
        'api:categories:read' => 3,
        'api:offers:read' => 7,
        'api:offers:write' => 11,
        'api:orders:read' => 10,
        'api:orders:write' => 8,
    ]) {
        $errors[] = 'operations.json must record the documented OAuth scopes for every scoped operation.';
    }
    $summary = contractOptionalObject($manifest, 'summary');
    if (($summary['schemas'] ?? null) !== 173) {
        $errors[] = 'operations.json must record 173 production schemas.';
    }
}

/**
 * @param array<string, mixed> $diff
 * @param array<string, mixed> $lock
 * @param list<string> $errors
 */
function validateDiff(array $diff, array $lock, array &$errors): void
{
    $from = contractOptionalObject($diff, 'from');
    $to = contractOptionalObject($diff, 'to');
    if (($from['sha256'] ?? null) !== ($lock['productionSha256'] ?? null)
        || ($to['sha256'] ?? null) !== ($lock['nextSha256'] ?? null)) {
        $errors[] = 'prod-next-diff.json does not match the contract lock.';
    }
    foreach (['operationChanges', 'schemaChanges'] as $group) {
        if (!is_array($diff[$group] ?? null)) {
            $errors[] = sprintf('prod-next-diff.%s must be an array.', $group);
            continue;
        }
        foreach ($diff[$group] as $index => $change) {
            if (!is_array($change) || !in_array($change['classification'] ?? null, ['additive', 'deprecated', 'validation', 'potentially-breaking'], true)) {
                $errors[] = sprintf('prod-next-diff.%s[%d] has no supported classification.', $group, $index);
            }
        }
    }
}

/**
 * @param array<string, mixed> $document
 * @param list<string> $errors
 */
function validateRules(array $document, array &$errors): void
{
    $rules = $document['rules'] ?? null;
    if (!is_array($rules) || $rules === []) {
        $errors[] = 'validation-rules.rules must be a non-empty array.';
        return;
    }
    $ids = [];
    foreach ($rules as $index => $rule) {
        if (!is_array($rule)) {
            $errors[] = sprintf('validation-rules.rules[%d] must be an object.', $index);
            continue;
        }
        $id = $rule['id'] ?? null;
        if (!is_string($id) || isset($ids[$id])) {
            $errors[] = sprintf('validation-rules.rules[%d] has a missing or duplicate ID.', $index);
        } else {
            $ids[$id] = true;
        }
        foreach (['constraint', 'status', 'target', 'unit'] as $field) {
            if (!is_string($rule[$field] ?? null) || $rule[$field] === '') {
                $errors[] = sprintf('validation-rules.rules[%d].%s must be non-empty.', $index, $field);
            }
        }
        foreach (['minimum', 'maximum'] as $bound) {
            if (!array_key_exists($bound, $rule)) {
                $errors[] = sprintf('validation-rules.rules[%d] must explicitly declare %s.', $index, $bound);
                continue;
            }
            if ($rule[$bound] !== null && (!is_array($rule[$bound]) || !is_numeric($rule[$bound]['value'] ?? null) || !is_bool($rule[$bound]['inclusive'] ?? null))) {
                $errors[] = sprintf('validation-rules.rules[%d].%s is invalid.', $index, $bound);
            }
        }
        if (array_key_exists('sourceOverride', $rule)) {
            if (!is_array($rule['sourceOverride'])) {
                $errors[] = sprintf('validation-rules.rules[%d].sourceOverride must be an object.', $index);
            } else {
                $override = contractObject($rule['sourceOverride'], sprintf('validation-rules.rules[%d].sourceOverride', $index));
                $overrideUrl = $override['url'] ?? null;
                if (!is_string($overrideUrl) || !str_starts_with($overrideUrl, 'https://inpsa-api-portal.inpost-group.com/')) {
                    $errors[] = sprintf('validation-rules.rules[%d].sourceOverride is not official.', $index);
                }
            }
        }
    }

    $source = contractOptionalObject($document, 'source');
    $sourceUrl = $source['url'] ?? null;
    if (!is_string($sourceUrl) || !str_starts_with($sourceUrl, 'https://inpsa-api-portal.inpost-group.com/')) {
        $errors[] = 'validation-rules.source must be an official portal URL.';
    }
    if (($source['productionStatus'] ?? null) !== 'verified-by-production-contract') {
        $errors[] = 'Validation rules must be verified against the production contract.';
    }
    if (($source['defaultForRulesWithoutSourceOverride'] ?? null) !== true
        || !is_string($source['releaseVersion'] ?? null)) {
        $errors[] = 'validation-rules.source must explicitly apply to every rule without an override.';
    }
}

/**
 * @param array<string, mixed> $document
 * @param list<string> $errors
 */
function validatePendingStage(array $document, array &$errors): void
{
    if (($document['allowedStatuses'] ?? null) !== ['documented', 'pending-stage', 'verified-stage', 'rejected-stage']) {
        $errors[] = 'pending-stage-verifications.allowedStatuses is incomplete.';
    }

    $environment = contractOptionalObject($document, 'environment');
    $safeguards = contractOptionalObject($environment, 'safeguards');
    if (($environment['credentialsAvailable'] ?? null) !== false
        || ($safeguards['defaultAllowWrites'] ?? null) !== false
        || ($safeguards['productionHostnameDenied'] ?? null) !== true) {
        $errors[] = 'Stage environment safeguards are incomplete.';
    }

    $items = $document['items'] ?? null;
    if (!is_array($items) || count($items) !== 12) {
        $errors[] = 'pending-stage-verifications.items must contain STAGE-001 through STAGE-012.';
        return;
    }
    foreach ($items as $index => $item) {
        $expectedId = sprintf('STAGE-%03d', $index + 1);
        if (!is_array($item) || ($item['id'] ?? null) !== $expectedId) {
            $errors[] = sprintf('Expected %s at pending-stage-verifications.items[%d].', $expectedId, $index);
            continue;
        }
        if (($item['status'] ?? null) !== 'pending-stage'
            || !is_string($item['owner'] ?? null)
            || !in_array($item['priority'] ?? null, ['critical', 'high', 'medium', 'low'], true)
            || !is_array($item['blocks'] ?? null)
            || $item['blocks'] === []) {
            $errors[] = sprintf('%s has incomplete status, owner, priority, or blocks.', $expectedId);
        }
        if (($item['evidence'] ?? null) !== null) {
            $errors[] = sprintf('%s must not have evidence before Stage execution.', $expectedId);
        }
    }
}

/**
 * @param list<array<string, mixed>> $documents
 * @param list<string> $errors
 */
function validateNoSecrets(array $documents, array &$errors): void
{
    $serialized = json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $patterns = [
        '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
        '/\\bBearer\\s+[A-Za-z0-9._~+\/-]{16,}/i',
        '/\\beyJ[A-Za-z0-9_-]{20,}\\.[A-Za-z0-9_-]{20,}/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $serialized) === 1) {
            $errors[] = 'A value resembling a credential was found in contract resources.';
            return;
        }
    }
}
