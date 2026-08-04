<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

try {
    [$productionSource, $nextSource, $lockSource, $target] = contractArguments(
        contractCliArguments(),
        4,
        'php tools/contract/check-drift.php <production.json> <next.json> <contract-lock.json> <report.json>'
    );

    $production = contractReadJson($productionSource);
    $next = contractReadJson($nextSource);
    $lock = contractReadJson($lockSource);
    contractAssertOpenApi($production, $productionSource);
    contractAssertOpenApi($next, $nextSource);

    $productionInfo = contractRequiredObject($production, 'info', 'Production OpenAPI');
    $nextInfo = contractRequiredObject($next, 'info', 'Next OpenAPI');
    $productionHash = contractSha256File($productionSource);
    $nextHash = contractSha256File($nextSource);
    $productionChanged = $productionHash !== ($lock['productionSha256'] ?? null);
    $nextChanged = $nextHash !== ($lock['nextSha256'] ?? null);

    contractWriteJson($target, [
        'checkedAgainst' => $lock['checkedAt'] ?? null,
        'next' => [
            'actualSha256' => $nextHash,
            'actualVersion' => contractRequiredString($nextInfo['version'] ?? null, 'Next contract version'),
            'changed' => $nextChanged,
            'lockedSha256' => $lock['nextSha256'] ?? null,
            'lockedVersion' => $lock['nextContractVersion'] ?? null,
        ],
        'production' => [
            'actualSha256' => $productionHash,
            'actualVersion' => contractRequiredString($productionInfo['version'] ?? null, 'Production contract version'),
            'changed' => $productionChanged,
            'lockedSha256' => $lock['productionSha256'] ?? null,
            'lockedVersion' => $lock['productionContractVersion'] ?? null,
        ],
        'status' => $productionChanged || $nextChanged ? 'contract-changed' : 'unchanged',
    ]);

    fwrite(STDOUT, sprintf("Contract drift status: %s.%s", $productionChanged || $nextChanged ? 'changed' : 'unchanged', PHP_EOL));
    exit($productionChanged || $nextChanged ? 2 : 0);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
