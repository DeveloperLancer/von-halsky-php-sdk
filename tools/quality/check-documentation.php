<?php

declare(strict_types=1);

const DOCUMENTATION_REQUIRED_FILES = [
    'CHANGELOG.md',
    'CONTRIBUTING.md',
    'README.md',
    'SECURITY.md',
    'docs/README.md',
    'docs/compatibility.md',
    'docs/installation.md',
    'examples/README.md',
    'resources/contract/README.md',
    'tools/contract/README.md',
    'tools/contract/STAGE.md',
];

$projectRoot = dirname(__DIR__, 2);
$errors = [];

foreach (DOCUMENTATION_REQUIRED_FILES as $relativePath) {
    $path = $projectRoot . '/' . $relativePath;
    if (!is_file($path)) {
        $errors[] = sprintf('Missing required documentation file: %s', $relativePath);
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = sprintf('Unable to read documentation file: %s', $relativePath);
        continue;
    }

    if (preg_match_all('/\[[^\]]+\]\((?<target>[^)]+)\)/', $content, $matches) === false) {
        $errors[] = sprintf('Unable to parse Markdown links in %s', $relativePath);
        continue;
    }

    foreach ($matches['target'] as $target) {
        if (documentationIsExternalLink($target)) {
            continue;
        }

        $targetPath = rawurldecode(explode('#', trim($target, '<>'), 2)[0]);
        if ($targetPath === '') {
            continue;
        }
        if (str_contains(str_replace('\\', '/', $targetPath), 'plan/')) {
            $errors[] = sprintf('%s links to ignored plan documentation: %s', $relativePath, $target);
            continue;
        }

        $resolved = dirname($path) . '/' . $targetPath;
        if (!is_file($resolved) && !is_dir($resolved)) {
            $errors[] = sprintf('%s contains a broken local link: %s', $relativePath, $target);
        }
    }
}

$matchedExampleFiles = glob($projectRoot . '/examples/*.php');
$exampleFiles = $matchedExampleFiles === false ? [] : $matchedExampleFiles;
foreach ($exampleFiles as $exampleFile) {
    $result = documentationRun([PHP_BINARY, '-l', $exampleFile], $projectRoot);
    if ($result['exitCode'] !== 0) {
        $errors[] = sprintf('Example has invalid PHP syntax: %s (%s)', basename($exampleFile), trim($result['stderr']));
    }
}

foreach (['src', 'tests', 'tools'] as $sourceDirectory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot . '/' . $sourceDirectory, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if ($content === false || preg_match('/\A<\?php\s+declare\(strict_types=1\);/', $content) !== 1) {
            $errors[] = sprintf('Project PHP file does not declare strict types: %s', str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1)));
        }
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, sprintf('Documentation is consistent (%d files, %d PHP examples).%s', count(DOCUMENTATION_REQUIRED_FILES), count($exampleFiles), PHP_EOL));

function documentationIsExternalLink(string $target): bool
{
    return str_starts_with($target, '#')
        || str_starts_with($target, 'https://')
        || str_starts_with($target, 'http://')
        || str_starts_with($target, 'mailto:');
}

/**
 * @param non-empty-list<string> $command
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function documentationRun(array $command, string $workingDirectory): array
{
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workingDirectory);
    if (!is_resource($process)) {
        return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unable to start process.'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exitCode' => proc_close($process),
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}
