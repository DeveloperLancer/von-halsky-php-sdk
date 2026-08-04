<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Contract;

use DevLancer\VonHalsky\Tests\Support\CliProcess;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class ContractToolsTest extends TestCase
{
    private string $projectRoot;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->temporaryDirectory = $this->projectRoot . '/build/tests/' . bin2hex(random_bytes(8));
        if (!mkdir($this->temporaryDirectory, 0777, true) && !is_dir($this->temporaryDirectory)) {
            throw new RuntimeException('Unable to create test directory.');
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->temporaryDirectory . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        rmdir($this->temporaryDirectory);
    }

    #[Test]
    public function toolsProduceDeterministicArtifactsFromSyntheticContract(): void
    {
        $fixture = $this->projectRoot . '/tests/Fixture/Contract/minimal-redoc.html';
        $extracted = $this->temporaryDirectory . '/extracted.json';
        $normalized = $this->temporaryDirectory . '/normalized.json';
        $normalizedAgain = $this->temporaryDirectory . '/normalized-again.json';
        $manifest = $this->temporaryDirectory . '/operations.json';
        $diff = $this->temporaryDirectory . '/diff.json';

        $this->assertSuccessful('extract-openapi.php', [$fixture, $extracted]);
        $this->assertSuccessful('normalize-openapi.php', [$extracted, $normalized]);
        $this->assertSuccessful('normalize-openapi.php', [$normalized, $normalizedAgain]);
        self::assertSame(hash_file('sha256', $normalized), hash_file('sha256', $normalizedAgain));

        $this->assertSuccessful('build-operation-manifest.php', [$normalized, $manifest]);
        $this->assertSuccessful('diff-openapi.php', [$normalized, $normalizedAgain, $diff]);

        self::assertFileExists($manifest);
        self::assertFileExists($diff);
        self::assertStringContainsString('"operations": 1', self::read($manifest));
        self::assertStringContainsString('"schemaChanges": 0', self::read($diff));
    }

    #[Test]
    public function committedContractResourcesPassCliValidator(): void
    {
        $result = CliProcess::run([
            PHP_BINARY,
            $this->projectRoot . '/tools/contract/validate-contract-data.php',
            $this->projectRoot . '/resources/contract',
        ], $this->projectRoot);

        self::assertSame(0, $result->exitCode, $result->stderr);
        self::assertStringContainsString('is valid', $result->stdout);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'extractor rejects non-Redoc HTML' => ['extract-openapi.php', ['README.md', 'output.json']];
        yield 'normalizer rejects malformed JSON' => ['normalize-openapi.php', ['tests/Fixture/Contract/invalid.json', 'output.json']];
        yield 'manifest rejects non-OpenAPI JSON' => ['build-operation-manifest.php', ['resources/contract/contract-lock.json', 'output.json']];
        yield 'diff rejects a missing input' => ['diff-openapi.php', ['tests/Fixture/Contract/minimal-openapi.json', 'missing.json', 'output.json']];
        yield 'validator rejects a missing directory' => ['validate-contract-data.php', ['missing-contract-directory']];
    }

    /** @param list<string> $arguments */
    #[DataProvider('invalidInputProvider')]
    #[Test]
    public function toolsRejectInvalidInput(string $script, array $arguments): void
    {
        $absoluteArguments = array_map(
            fn (string $argument): string => $argument === 'output.json'
                ? $this->temporaryDirectory . '/invalid-output.json'
                : $this->projectRoot . '/' . $argument,
            $arguments,
        );
        $result = CliProcess::run([
            PHP_BINARY,
            $this->projectRoot . '/tools/contract/' . $script,
            ...$absoluteArguments,
        ], $this->projectRoot);

        self::assertNotSame(0, $result->exitCode, $script . ' unexpectedly accepted invalid input.');
        self::assertStringContainsString('ERROR:', $result->stderr);
        self::assertFileDoesNotExist($this->temporaryDirectory . '/invalid-output.json');
    }

    /** @param list<string> $arguments */
    private function assertSuccessful(string $script, array $arguments): void
    {
        $result = CliProcess::run([
            PHP_BINARY,
            $this->projectRoot . '/tools/contract/' . $script,
            ...$arguments,
        ], $this->projectRoot);

        self::assertSame(0, $result->exitCode, $result->stderr);
    }

    private static function read(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read ' . $path);
        }

        return $content;
    }
}
