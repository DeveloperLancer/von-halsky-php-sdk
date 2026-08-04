<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use RuntimeException;

final class CliProcess
{
    /**
     * @param non-empty-list<string> $command
     */
    public static function run(array $command, string $workingDirectory): CliResult
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $workingDirectory);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start CLI process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($stdout === false || $stderr === false) {
            throw new RuntimeException('Unable to read CLI process output.');
        }

        return new CliResult($exitCode, $stdout, $stderr);
    }
}
