<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Contract;

use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class ContractArtifactsTest extends TestCase
{
    private const PRODUCTION_HASH = 'efa9827f60e7cf7b2c84d005380d5038461b8c183cce99867c8f8b782c3d3091';

    #[Test]
    public function lockReferencesOnlyOfficialSourcesAndLocalDerivatives(): void
    {
        $lock = self::readObject('contract-lock.json');

        self::assertSame('3.0.3', $lock['openApiVersion'] ?? null);
        self::assertSame('1.5.11', $lock['productionContractVersion'] ?? null);
        self::assertSame(self::PRODUCTION_HASH, $lock['productionSha256'] ?? null);
        self::assertFalse(self::nestedValue($lock, ['redistribution', 'fullOpenApiCommitted']));

        $sources = self::listValue($lock, 'sources');
        self::assertGreaterThanOrEqual(8, count($sources));
        foreach ($sources as $sourceValue) {
            $source = self::objectValue($sourceValue, 'source');
            self::assertIsString($source['url'] ?? null);
            self::assertStringStartsWith('https://inpsa-api-portal.inpost-group.com/', $source['url']);
        }
    }

    #[Test]
    public function productionManifestHasExpectedCoverage(): void
    {
        $manifest = self::readObject('operations.json');
        self::assertSame(self::PRODUCTION_HASH, $manifest['generatedFromSha256'] ?? null);
        self::assertSame(169, self::nestedValue($manifest, ['summary', 'schemas']));

        $operations = self::listValue($manifest, 'operations');
        self::assertCount(42, $operations);

        $operationIds = [];
        $methodPaths = [];
        $paths = [];
        $phaseCounts = [];
        $deprecated = [];

        foreach ($operations as $operationValue) {
            $operation = self::objectValue($operationValue, 'operation');
            $operationId = self::requiredString($operation, 'operationId');
            $method = self::requiredString($operation, 'method');
            $path = self::requiredString($operation, 'path');
            $phase = $operation['phase'] ?? null;
            self::assertIsInt($phase);

            $operationIds[] = $operationId;
            $methodPaths[] = $method . ' ' . $path;
            $paths[] = $path;
            $phaseCounts[$phase] = ($phaseCounts[$phase] ?? 0) + 1;

            self::assertNotSame('', self::requiredString($operation, 'domain'));
            self::assertArrayHasKey('scope', $operation);
            self::assertIsArray($operation['scopes'] ?? null);

            if (($operation['deprecated'] ?? false) === true) {
                $deprecated[] = $operation;
                self::assertTrue(
                    is_string($operation['alternative'] ?? null) || is_string($operation['deprecationReason'] ?? null),
                    $method . ' ' . $path . ' needs a migration decision.',
                );
            }
        }

        self::assertCount(42, array_unique($operationIds));
        self::assertCount(42, array_unique($methodPaths));
        self::assertCount(38, array_unique($paths));
        self::assertSame([5 => 4, 6 => 18, 7 => 20], $phaseCounts);
        self::assertCount(2, $deprecated);
    }

    #[Test]
    public function implementationCoverageRecordsEveryProductionOperation(): void
    {
        $coverage = self::readObject('implementation-coverage.json');
        $manifest = self::readObject('operations.json');

        self::assertSame(42, self::nestedValue($coverage, ['summary', 'implemented']));
        self::assertSame(42, self::nestedValue($coverage, ['summary', 'total']));
        $operations = self::listValue($coverage, 'implementedOperations');
        $manifestOperations = self::listValue($manifest, 'operations');
        $implementedIds = array_column($operations, 'operationId');
        $manifestIds = array_column($manifestOperations, 'operationId');
        sort($implementedIds);
        sort($manifestIds);
        self::assertSame($manifestIds, $implementedIds);
    }

    #[Test]
    public function nextDiffIsLockedAndEveryChangeIsClassified(): void
    {
        $lock = self::readObject('contract-lock.json');
        $diff = self::readObject('prod-next-diff.json');

        self::assertSame($lock['productionSha256'] ?? null, self::nestedValue($diff, ['from', 'sha256']));
        self::assertSame($lock['nextSha256'] ?? null, self::nestedValue($diff, ['to', 'sha256']));

        $changes = array_merge(
            self::listValue($diff, 'operationChanges'),
            self::listValue($diff, 'schemaChanges'),
        );
        self::assertCount(18, $changes);
        foreach ($changes as $changeValue) {
            $change = self::objectValue($changeValue, 'change');
            self::assertContains(
                $change['classification'] ?? null,
                ['additive', 'deprecated', 'validation', 'potentially-breaking'],
            );
        }
    }

    #[Test]
    public function validationRulesHaveExplicitProvenanceAndBounds(): void
    {
        $document = self::readObject('validation-rules.json');
        self::assertSame('scheduled-not-verified', self::nestedValue($document, ['source', 'productionStatus']));

        $rules = self::listValue($document, 'rules');
        self::assertCount(21, $rules);
        foreach ($rules as $ruleValue) {
            $rule = self::objectValue($ruleValue, 'rule');
            self::assertNotSame('', self::requiredString($rule, 'id'));
            self::assertNotSame('', self::requiredString($rule, 'target'));
            self::assertNotSame('', self::requiredString($rule, 'unit'));
            self::assertArrayHasKey('minimum', $rule);
            self::assertArrayHasKey('maximum', $rule);
            self::assertStringStartsWith('official-next-', self::requiredString($rule, 'status'));
        }
    }

    #[Test]
    public function deferredStageChecklistIsCompleteAndContainsNoEvidence(): void
    {
        $document = self::readObject('pending-stage-verifications.json');
        self::assertFalse(self::nestedValue($document, ['environment', 'credentialsAvailable']));
        self::assertFalse(self::nestedValue($document, ['environment', 'safeguards', 'defaultAllowWrites']));
        self::assertTrue(self::nestedValue($document, ['environment', 'safeguards', 'productionHostnameDenied']));

        $items = self::listValue($document, 'items');
        self::assertCount(12, $items);
        foreach ($items as $index => $itemValue) {
            $item = self::objectValue($itemValue, 'Stage checklist item');
            self::assertSame(sprintf('STAGE-%03d', $index + 1), $item['id'] ?? null);
            self::assertSame('pending-stage', $item['status'] ?? null);
            self::assertNull($item['evidence'] ?? null);
            self::assertNotSame('', self::requiredString($item, 'owner'));
            self::assertNotEmpty($item['blocks'] ?? null);
        }
    }

    /** @return array<string, mixed> */
    private static function readObject(string $file): array
    {
        $path = dirname(__DIR__, 2) . '/resources/contract/' . $file;
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read ' . $path);
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON in ' . $path, 0, $exception);
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Expected JSON object in ' . $path);
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Expected string JSON keys in ' . $path);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $object
     * @return list<mixed>
     */
    private static function listValue(array $object, string $key): array
    {
        $value = $object[$key] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new RuntimeException($key . ' must be a JSON array.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $object
     */
    private static function requiredString(array $object, string $key): string
    {
        $value = $object[$key] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException($key . ' must be a string.');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function objectValue(mixed $value, string $label): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException($label . ' must be a JSON object.');
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException($label . ' must use string keys.');
            }
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $object
     * @param non-empty-list<string> $path
     */
    private static function nestedValue(array $object, array $path): mixed
    {
        $value = $object;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                throw new RuntimeException(implode('.', $path) . ' does not exist.');
            }
            $value = $value[$key];
        }

        return $value;
    }
}
