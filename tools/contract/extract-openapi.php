<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

contractRun(static function (): void {
    [$source, $target] = contractArguments(
        contractCliArguments(),
        2,
        'php tools/contract/extract-openapi.php <official-redoc-url-or-file> <output.json>'
    );

    $document = contractExtractOpenApi(contractRead($source), $source);
    contractWriteJson($target, $document);

    $info = contractRequiredObject($document, 'info', 'OpenAPI document');
    fwrite(STDOUT, sprintf(
        "Extracted OpenAPI %s contract %s to %s.%s",
        contractRequiredString($document['openapi'] ?? null, 'OpenAPI version'),
        contractRequiredString($info['version'] ?? null, 'Contract version'),
        $target,
        PHP_EOL
    ));
});
