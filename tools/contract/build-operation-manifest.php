<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

contractRun(static function (): void {
    [$source, $target] = contractArguments(
        contractCliArguments(),
        2,
        'php tools/contract/build-operation-manifest.php <normalized-openapi.json> <operations.json>'
    );

    $document = contractReadJson($source);
    contractAssertOpenApi($document, $source);

    $operations = [];
    $operationIds = [];
    $methodPaths = [];
    $domains = [];
    $phases = [];

    foreach (contractOperations($document) as $entry) {
        $operation = $entry['operation'];
        $operationId = $operation['operationId'] ?? null;
        if (!is_string($operationId) || $operationId === '') {
            throw new RuntimeException(sprintf('%s %s has no operationId.', $entry['method'], $entry['path']));
        }

        $methodPath = $entry['method'] . ' ' . $entry['path'];
        if (isset($operationIds[$operationId])) {
            throw new RuntimeException(sprintf('Duplicate operationId "%s".', $operationId));
        }
        if (isset($methodPaths[$methodPath])) {
            throw new RuntimeException(sprintf('Duplicate operation "%s".', $methodPath));
        }
        $operationIds[$operationId] = true;
        $methodPaths[$methodPath] = true;

        $tags = $operation['tags'] ?? null;
        $tag = is_array($tags) ? ($tags[0] ?? null) : null;
        if (!is_string($tag)) {
            throw new RuntimeException(sprintf('%s has no primary tag.', $methodPath));
        }

        [$domain, $phase] = manifestDomainAndPhase($tag, $entry['path']);
        $scopes = manifestScopes($operation, $document);
        $deprecated = ($operation['deprecated'] ?? false) === true;
        [$alternative, $deprecationReason] = manifestDeprecation($methodPath, $deprecated);

        $record = [
            'alternative' => $alternative,
            'deprecated' => $deprecated,
            'deprecationReason' => $deprecationReason,
            'domain' => $domain,
            'method' => $entry['method'],
            'operationId' => $operationId,
            'path' => $entry['path'],
            'phase' => $phase,
            'scope' => $scopes[0] ?? null,
            'scopes' => $scopes,
            'tag' => $tag,
            'verification' => 'official-contract',
            'version' => manifestPathVersion($entry['path']),
        ];

        $domains[$domain] = ($domains[$domain] ?? 0) + 1;
        $phases[(string) $phase] = ($phases[(string) $phase] ?? 0) + 1;
        $operations[] = $record;
    }

    ksort($domains, SORT_STRING);
    ksort($phases, SORT_NUMERIC);

    $info = contractRequiredObject($document, 'info', 'OpenAPI document');
    $paths = contractRequiredObject($document, 'paths', 'OpenAPI document');
    $components = contractOptionalObject($document, 'components');
    $schemas = contractOptionalObject($components, 'schemas');

    contractWriteJson($target, [
        'contractVersion' => contractRequiredString($info['version'] ?? null, 'Contract version'),
        'generatedFromSha256' => contractSha256File($source),
        'operations' => $operations,
        'openApiVersion' => $document['openapi'],
        'summary' => [
            'deprecated' => count(array_filter($operations, static fn (array $item): bool => $item['deprecated'])),
            'domains' => $domains,
            'operations' => count($operations),
            'paths' => count($paths),
            'phases' => $phases,
            'schemas' => count($schemas),
        ],
    ]);

    fwrite(STDOUT, sprintf("Built manifest with %d operations at %s.%s", count($operations), $target, PHP_EOL));
});

/** @return array{string, int} */
function manifestDomainAndPhase(string $tag, string $path): array
{
    if ($tag === 'Organizations') {
        return ['organizations', 5];
    }
    if ($tag === 'Categories') {
        return ['categories', 5];
    }
    if ($tag === 'Offer Attachments') {
        return ['attachments', 6];
    }
    if ($tag === 'Offers') {
        return ['offers', 6];
    }
    if ($tag !== 'Orders') {
        throw new RuntimeException(sprintf('Unknown API tag "%s" for path "%s".', $tag, $path));
    }

    if (str_contains($path, '/returns')) {
        return ['returns', 7];
    }
    if (str_ends_with($path, '/refund') || str_ends_with($path, '/partial-refund')) {
        return ['refunds', 7];
    }
    if (str_contains($path, '/claims')) {
        return ['claims', 7];
    }

    return ['orders', 7];
}

/**
 * @param array<string, mixed> $operation
 * @param array<string, mixed> $document
 * @return list<string>
 */
function manifestScopes(array $operation, array $document): array
{
    $security = array_key_exists('security', $operation) ? $operation['security'] : ($document['security'] ?? []);
    if (!is_array($security)) {
        return [];
    }

    $scopes = [];
    foreach ($security as $requirement) {
        if (!is_array($requirement)) {
            continue;
        }
        foreach ($requirement as $requiredScopes) {
            if (!is_array($requiredScopes)) {
                continue;
            }
            foreach ($requiredScopes as $scope) {
                if (is_string($scope) && $scope !== '') {
                    $scopes[$scope] = true;
                }
            }
        }
    }

    $result = array_keys($scopes);
    sort($result, SORT_STRING);
    return $result;
}

/** @return array{?string, ?string} */
function manifestDeprecation(string $methodPath, bool $deprecated): array
{
    if (!$deprecated) {
        return [null, null];
    }

    if ($methodPath === 'GET /v1/orders/delivery-methods') {
        return ['GET /v2/orders/delivery-methods', 'The official contract recommends the newer endpoint version.'];
    }

    if ($methodPath === 'POST /v1/organizations/{organizationId}/orders/{orderId}/refuse') {
        return [null, 'The official production contract marks this operation deprecated but does not document a replacement.'];
    }

    throw new RuntimeException(sprintf('Deprecated operation "%s" needs an explicit migration decision.', $methodPath));
}

function manifestPathVersion(string $path): int
{
    if (preg_match('~^/v(?<version>[1-9][0-9]*)(?:/|$)~', $path, $matches) !== 1) {
        throw new RuntimeException(sprintf('Path "%s" has no API version prefix.', $path));
    }

    return (int) $matches['version'];
}
