<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

contractRun(static function (): void {
    [$productionSource, $nextSource, $target] = contractArguments(
        contractCliArguments(),
        3,
        'php tools/contract/diff-openapi.php <production.json> <next.json> <diff.json>'
    );

    $production = contractReadJson($productionSource);
    $next = contractReadJson($nextSource);
    contractAssertOpenApi($production, $productionSource);
    contractAssertOpenApi($next, $nextSource);

    $operationChanges = diffOperations($production, $next);
    $productionInfo = contractRequiredObject($production, 'info', 'Production OpenAPI');
    $nextInfo = contractRequiredObject($next, 'info', 'Next OpenAPI');
    $productionComponents = contractOptionalObject($production, 'components');
    $nextComponents = contractOptionalObject($next, 'components');
    $schemaChanges = diffNamedObjects(
        contractOptionalObject($productionComponents, 'schemas'),
        contractOptionalObject($nextComponents, 'schemas')
    );

    contractWriteJson($target, [
        'from' => [
            'contractVersion' => contractRequiredString($productionInfo['version'] ?? null, 'Production contract version'),
            'sha256' => contractSha256File($productionSource),
        ],
        'operationChanges' => $operationChanges,
        'schemaChanges' => $schemaChanges,
        'summary' => [
            'operationChanges' => count($operationChanges),
            'schemaChanges' => count($schemaChanges),
        ],
        'to' => [
            'contractVersion' => contractRequiredString($nextInfo['version'] ?? null, 'Next contract version'),
            'sha256' => contractSha256File($nextSource),
        ],
    ]);

    fwrite(STDOUT, sprintf(
        "Found %d operation changes and %d schema changes; report written to %s.%s",
        count($operationChanges),
        count($schemaChanges),
        $target,
        PHP_EOL
    ));
});

/**
 * @param array<string, mixed> $production
 * @param array<string, mixed> $next
 * @return list<array<string, mixed>>
 */
function diffOperations(array $production, array $next): array
{
    $before = diffOperationMap($production);
    $after = diffOperationMap($next);
    $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
    sort($keys, SORT_STRING);
    $changes = [];

    foreach ($keys as $key) {
        if (!isset($before[$key])) {
            $changes[] = ['change' => 'added', 'classification' => 'additive', 'operation' => $key];
            continue;
        }
        if (!isset($after[$key])) {
            $changes[] = ['change' => 'removed', 'classification' => 'potentially-breaking', 'operation' => $key];
            continue;
        }

        $wasDeprecated = ($before[$key]['deprecated'] ?? false) === true;
        $isDeprecated = ($after[$key]['deprecated'] ?? false) === true;
        if (!$wasDeprecated && $isDeprecated) {
            $changes[] = ['change' => 'deprecated', 'classification' => 'deprecated', 'operation' => $key];
        }

        if (hash('sha256', json_encode(contractNormalize($before[$key]), JSON_THROW_ON_ERROR)) !== hash('sha256', json_encode(contractNormalize($after[$key]), JSON_THROW_ON_ERROR))) {
            $changes[] = [
                'change' => 'contract-changed',
                'classification' => $wasDeprecated !== $isDeprecated ? 'deprecated' : 'potentially-breaking',
                'operation' => $key,
            ];
        }
    }

    return $changes;
}

/**
 * @param array<string, mixed> $document
 * @return array<string, array<string, mixed>>
 */
function diffOperationMap(array $document): array
{
    $result = [];
    foreach (contractOperations($document) as $entry) {
        $result[$entry['method'] . ' ' . $entry['path']] = $entry['operation'];
    }
    ksort($result, SORT_STRING);
    return $result;
}

/**
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 * @return list<array<string, string>>
 */
function diffNamedObjects(array $before, array $after): array
{
    $names = array_unique(array_merge(array_keys($before), array_keys($after)));
    sort($names, SORT_STRING);
    $changes = [];

    foreach ($names as $name) {
        if (!array_key_exists($name, $before)) {
            $changes[] = ['change' => 'added', 'classification' => 'additive', 'name' => $name];
        } elseif (!array_key_exists($name, $after)) {
            $changes[] = ['change' => 'removed', 'classification' => 'potentially-breaking', 'name' => $name];
        } elseif (contractNormalize($before[$name]) !== contractNormalize($after[$name])) {
            $changes[] = [
                'change' => 'contract-changed',
                'classification' => diffSchemaClassification($before[$name], $after[$name]),
                'name' => $name,
            ];
        }
    }

    return $changes;
}

function diffSchemaClassification(mixed $before, mixed $after): string
{
    return contractNormalize(diffWithoutValidationConstraints($before))
        === contractNormalize(diffWithoutValidationConstraints($after))
        ? 'validation'
        : 'potentially-breaking';
}

/** @return mixed */
function diffWithoutValidationConstraints(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    $validationKeywords = [
        'exclusiveMaximum',
        'exclusiveMinimum',
        'maxItems',
        'maxLength',
        'maximum',
        'minItems',
        'minLength',
        'minimum',
        'multipleOf',
        'pattern',
    ];

    $result = [];
    foreach ($value as $key => $item) {
        if (is_string($key) && in_array($key, $validationKeywords, true)) {
            continue;
        }
        $result[$key] = diffWithoutValidationConstraints($item);
    }

    return $result;
}
