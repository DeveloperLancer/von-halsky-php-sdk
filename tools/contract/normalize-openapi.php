<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

contractRun(static function (): void {
    [$source, $target] = contractArguments(
        contractCliArguments(),
        2,
        'php tools/contract/normalize-openapi.php <input.json> <output.json>'
    );

    $document = contractReadJson($source);
    contractAssertOpenApi($document, $source);
    contractWriteJson($target, $document);

    fwrite(STDOUT, sprintf("Normalized OpenAPI document written to %s.%s", $target, PHP_EOL));
});
