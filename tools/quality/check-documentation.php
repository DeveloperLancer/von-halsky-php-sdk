<?php

declare(strict_types=1);

const DOCUMENTATION_EXCLUDED_DIRECTORIES = [
    '.git',
    '.idea',
    'build',
    'plan',
    'var',
    'vendor',
];

/** One Markdown page for every public resource operation in each language. */
const DOCUMENTATION_OPERATION_PAGES = [
    'attachments/delete.md',
    'attachments/download.md',
    'attachments/list.md',
    'attachments/upload.md',
    'categories/attributes.md',
    'categories/get.md',
    'categories/list.md',
    'claims/get.md',
    'claims/list.md',
    'claims/partial-refund.md',
    'claims/refund.md',
    'claims/reject.md',
    'claims/types.md',
    'offers/close.md',
    'offers/command.md',
    'offers/create-batch.md',
    'offers/create.md',
    'offers/deposit-types.md',
    'offers/events.md',
    'offers/get.md',
    'offers/hints.md',
    'offers/list.md',
    'offers/patch.md',
    'offers/reopen.md',
    'offers/update-attributes.md',
    'offers/update-prices.md',
    'offers/update-stocks.md',
    'orders/accept.md',
    'orders/command.md',
    'orders/delivery-methods.md',
    'orders/events.md',
    'orders/get.md',
    'orders/list.md',
    'orders/refund.md',
    'organizations/list.md',
    'returns/accept.md',
    'returns/for-order.md',
    'returns/get.md',
    'returns/list.md',
    'returns/reject.md',
];

const DOCUMENTATION_LOCALE_ENTRY_PAGES = [
    'en/README.md',
    'en/installation.md',
    'en/client-and-environments.md',
    'en/authentication.md',
    'en/catalogue-and-offers.md',
    'en/orders-and-post-sale.md',
    'en/responses-and-errors.md',
    'en/reliability.md',
    'en/production-checklist.md',
    'en/compatibility.md',
    'en/api-reference.md',
    'pl/README.md',
    'pl/instalacja.md',
    'pl/klient-i-srodowiska.md',
    'pl/uwierzytelnianie.md',
    'pl/katalog-i-oferty.md',
    'pl/zamowienia-i-posprzedaz.md',
    'pl/odpowiedzi-i-bledy.md',
    'pl/niezawodnosc.md',
    'pl/checklista-produkcyjna.md',
    'pl/kompatybilnosc.md',
    'pl/referencja-php.md',
];

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$markdownFiles = documentationMarkdownFiles($projectRoot);

documentationCheckContractOperationCount($projectRoot, $errors);
documentationCheckLocaleCoverage($projectRoot, $errors);
$exampleFiles = documentationGlob($projectRoot . '/examples/*.php');

foreach ($markdownFiles as $path) {
    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = sprintf('Unable to read documentation file: %s', documentationRelativePath($projectRoot, $path));
        continue;
    }

    documentationCheckLinks($projectRoot, $path, $content, $errors);
    documentationCheckPhpFences($projectRoot, $path, $content, $errors);
}

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
            $errors[] = sprintf('Project PHP file does not declare strict types: %s', documentationRelativePath($projectRoot, $file->getPathname()));
        }
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, sprintf('Documentation is consistent (%d Markdown files, %d PHP examples).%s', count($markdownFiles), count($exampleFiles), PHP_EOL));

/** @return list<string> */
function documentationMarkdownFiles(string $projectRoot): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'md') {
            continue;
        }
        $relativePath = documentationRelativePath($projectRoot, $file->getPathname());
        foreach (DOCUMENTATION_EXCLUDED_DIRECTORIES as $directory) {
            if (str_starts_with($relativePath, $directory . '/')) {
                continue 2;
            }
        }
        $files[] = $file->getPathname();
    }

    sort($files);

    return $files;
}

/** @param list<string> $errors */
function documentationCheckLocaleCoverage(string $projectRoot, array &$errors): void
{
    foreach (DOCUMENTATION_LOCALE_ENTRY_PAGES as $relativePath) {
        if (!is_file($projectRoot . '/docs/' . $relativePath)) {
            $errors[] = sprintf('Missing localized documentation entry page: docs/%s', $relativePath);
        }
    }

    foreach (['en', 'pl'] as $locale) {
        $referenceRoot = $projectRoot . '/docs/' . $locale . '/reference';
        $actual = [];
        foreach (documentationGlob($referenceRoot . '/*/*.md') as $path) {
            if (basename($path) === 'README.md') {
                continue;
            }
            $actual[] = str_replace('\\', '/', substr($path, strlen($referenceRoot) + 1));
        }
        sort($actual);

        $expected = DOCUMENTATION_OPERATION_PAGES;
        sort($expected);
        foreach (array_diff($expected, $actual) as $missing) {
            $errors[] = sprintf('Missing %s operation reference page: docs/%s/reference/%s', $locale, $locale, $missing);
        }
        foreach (array_diff($actual, $expected) as $unexpected) {
            $errors[] = sprintf('Unexpected %s operation reference page: docs/%s/reference/%s', $locale, $locale, $unexpected);
        }
        if (count($actual) !== 40) {
            $errors[] = sprintf('Expected exactly 40 %s operation reference pages, found %d.', $locale, count($actual));
        }
    }
}

/** @return list<string> */
function documentationGlob(string $pattern): array
{
    $files = glob($pattern);

    return $files === false ? [] : $files;
}

/** @param list<string> $errors */
function documentationCheckContractOperationCount(string $projectRoot, array &$errors): void
{
    $path = $projectRoot . '/resources/contract/implementation-coverage.json';
    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = 'Unable to read resources/contract/implementation-coverage.json.';
        return;
    }

    try {
        $coverage = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $errors[] = 'resources/contract/implementation-coverage.json contains invalid JSON.';
        return;
    }

    $operations = is_array($coverage) ? ($coverage['implementedOperations'] ?? null) : null;
    if (!is_array($operations) || !array_is_list($operations)) {
        $errors[] = 'Contract coverage does not contain an implementedOperations list.';
        return;
    }

    if (count($operations) !== count(DOCUMENTATION_OPERATION_PAGES)) {
        $errors[] = sprintf(
            'Contract coverage has %d operations, but documentation expects %d operation pages per locale.',
            count($operations),
            count(DOCUMENTATION_OPERATION_PAGES),
        );
    }
}

/** @param list<string> $errors */
function documentationCheckLinks(string $projectRoot, string $path, string $content, array &$errors): void
{
    if (preg_match_all('/\[[^\]]+\]\((?<target>[^)]+)\)/', $content, $matches) === false) {
        $errors[] = sprintf('Unable to parse Markdown links in %s', documentationRelativePath($projectRoot, $path));
        return;
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
            $errors[] = sprintf('%s links to ignored plan documentation: %s', documentationRelativePath($projectRoot, $path), $target);
            continue;
        }
        $resolved = dirname($path) . '/' . $targetPath;
        if (!is_file($resolved) && !is_dir($resolved)) {
            $errors[] = sprintf('%s contains a broken local link: %s', documentationRelativePath($projectRoot, $path), $target);
        }
    }
}

/** @param list<string> $errors */
function documentationCheckPhpFences(string $projectRoot, string $path, string $content, array &$errors): void
{
    $matched = preg_match_all('/```php[ \t]*\R(?<code>.*?)(?:\R```|\z)/s', $content, $matches);
    if ($matched === false) {
        $errors[] = sprintf('Unable to parse PHP fences in %s', documentationRelativePath($projectRoot, $path));
        return;
    }

    foreach ($matches['code'] as $index => $code) {
        $source = ltrim($code);
        if (!str_starts_with($source, '<?php')) {
            $source = "<?php\n" . $source;
        }
        $temporaryFile = tempnam(sys_get_temp_dir(), 'von-halsky-docs-');
        if ($temporaryFile === false || file_put_contents($temporaryFile, $source) === false) {
            $errors[] = sprintf('Unable to create temporary PHP fence file for %s', documentationRelativePath($projectRoot, $path));
            continue;
        }

        $result = documentationRun([PHP_BINARY, '-l', $temporaryFile], $projectRoot);
        unlink($temporaryFile);
        if ($result['exitCode'] !== 0) {
            $errors[] = sprintf(
                'PHP fence %d has invalid syntax in %s (%s)',
                $index + 1,
                documentationRelativePath($projectRoot, $path),
                trim($result['stderr']),
            );
        }
    }
}

function documentationRelativePath(string $projectRoot, string $path): string
{
    return str_replace('\\', '/', substr($path, strlen($projectRoot) + 1));
}

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
