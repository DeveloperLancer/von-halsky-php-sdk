<?php

declare(strict_types=1);

const CONTRACT_HTTP_USER_AGENT = 'dev-lancer/von-halsky-php-sdk contract tool';
const CONTRACT_HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace'];

/**
 * @param list<string> $arguments
 * @return list<string>
 */
function contractArguments(array $arguments, int $expectedCount, string $usage): array
{
    array_shift($arguments);

    if ($arguments === ['--help'] || $arguments === ['-h']) {
        fwrite(STDOUT, $usage . PHP_EOL);
        exit(0);
    }

    if (count($arguments) !== $expectedCount) {
        throw new InvalidArgumentException('Usage: ' . $usage);
    }

    return $arguments;
}

/** @return list<string> */
function contractCliArguments(): array
{
    $arguments = $_SERVER['argv'] ?? [];
    if (!is_array($arguments)) {
        throw new RuntimeException('CLI arguments are unavailable.');
    }

    $result = [];
    foreach ($arguments as $argument) {
        if (!is_string($argument)) {
            throw new RuntimeException('Every CLI argument must be a string.');
        }
        $result[] = $argument;
    }

    return $result;
}

/** @return array<string, mixed> */
function contractReadJson(string $source): array
{
    $content = contractRead($source);

    try {
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException(sprintf('Invalid JSON in "%s": %s', $source, $exception->getMessage()), 0, $exception);
    }

    return contractObject($decoded, sprintf('JSON document in "%s"', $source));
}

/** @return array<string, mixed> */
function contractObject(mixed $value, string $label): array
{
    if (!is_array($value) || array_is_list($value)) {
        throw new RuntimeException(sprintf('%s must be an object.', $label));
    }

    $result = [];
    foreach ($value as $key => $item) {
        if (!is_string($key)) {
            throw new RuntimeException(sprintf('%s must use string keys.', $label));
        }
        $result[$key] = $item;
    }

    return $result;
}

/**
 * @param array<string, mixed> $object
 * @return array<string, mixed>
 */
function contractRequiredObject(array $object, string $key, string $label): array
{
    return contractObject($object[$key] ?? null, $label . '.' . $key);
}

/**
 * @param array<string, mixed> $object
 * @return array<string, mixed>
 */
function contractOptionalObject(array $object, string $key): array
{
    if (!array_key_exists($key, $object)) {
        return [];
    }

    return contractObject($object[$key], $key);
}

function contractRequiredString(mixed $value, string $label): string
{
    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('%s must be a non-empty string.', $label));
    }

    return $value;
}

function contractSha256File(string $path): string
{
    $hash = hash_file('sha256', $path);
    if ($hash === false) {
        throw new RuntimeException(sprintf('Unable to hash "%s".', $path));
    }

    return $hash;
}

function contractRead(string $source): string
{
    if ($source === '-') {
        $content = stream_get_contents(STDIN);
    } elseif (filter_var($source, FILTER_VALIDATE_URL) !== false) {
        $context = stream_context_create([
            'http' => [
                'follow_location' => 1,
                'header' => 'User-Agent: ' . CONTRACT_HTTP_USER_AGENT,
                'ignore_errors' => true,
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $content = @file_get_contents($source, false, $context);
    } else {
        $content = @file_get_contents($source);
    }

    if ($content === false) {
        $error = error_get_last();
        throw new RuntimeException(sprintf(
            'Unable to read "%s"%s.',
            $source,
            isset($error['message']) ? ': ' . $error['message'] : ''
        ));
    }

    return $content;
}

/** @param mixed $value */
function contractWriteJson(string $target, mixed $value): void
{
    $directory = dirname($target);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
    }

    try {
        $json = json_encode(
            contractNormalize($value),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    } catch (JsonException $exception) {
        throw new RuntimeException('Unable to encode JSON: ' . $exception->getMessage(), 0, $exception);
    }

    if (file_put_contents($target, $json . PHP_EOL) === false) {
        throw new RuntimeException(sprintf('Unable to write "%s".', $target));
    }
}

/** @param mixed $value @return mixed */
function contractNormalize(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    if (array_is_list($value)) {
        return array_map(static fn (mixed $item): mixed => contractNormalize($item), $value);
    }

    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {
        $value[$key] = contractNormalize($item);
    }

    return $value;
}

/** @param array<string, mixed> $document */
function contractAssertOpenApi(array $document, string $source): void
{
    $version = $document['openapi'] ?? null;
    $paths = $document['paths'] ?? null;

    if (!is_string($version) || !str_starts_with($version, '3.') || !is_array($paths)) {
        throw new RuntimeException(sprintf('No valid OpenAPI 3 document found in "%s".', $source));
    }
}

/** @return array<string, mixed> */
function contractExtractOpenApi(string $content, string $source): array
{
    try {
        $direct = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($direct) && !array_is_list($direct)) {
            $document = contractObject($direct, 'OpenAPI document');
            contractAssertOpenApi($document, $source);
            return $document;
        }
    } catch (JsonException) {
        // HTML is the normal input format for the official Redoc pages.
    }

    $marker = 'const __redoc_state =';
    $markerPosition = strpos($content, $marker);
    if ($markerPosition === false) {
        throw new RuntimeException(sprintf('Redoc state not found in "%s".', $source));
    }

    $jsonStart = strpos($content, '{', $markerPosition + strlen($marker));
    if ($jsonStart === false) {
        throw new RuntimeException(sprintf('Redoc state has no JSON object in "%s".', $source));
    }

    $json = contractExtractJsonObject($content, $jsonStart, $source);
    try {
        $decodedState = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException(sprintf('Invalid Redoc state in "%s": %s', $source, $exception->getMessage()), 0, $exception);
    }

    $state = contractObject($decodedState, 'Redoc state');
    $spec = contractRequiredObject($state, 'spec', 'Redoc state');
    $document = contractRequiredObject($spec, 'data', 'Redoc state.spec');

    contractAssertOpenApi($document, $source);
    return $document;
}

function contractExtractJsonObject(string $content, int $start, string $source): string
{
    $length = strlen($content);
    $depth = 0;
    $inString = false;
    $escaped = false;

    for ($position = $start; $position < $length; ++$position) {
        $character = $content[$position];

        if ($inString) {
            if ($escaped) {
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === '"') {
                $inString = false;
            }
            continue;
        }

        if ($character === '"') {
            $inString = true;
        } elseif ($character === '{') {
            ++$depth;
        } elseif ($character === '}') {
            --$depth;
            if ($depth === 0) {
                return substr($content, $start, $position - $start + 1);
            }
        }
    }

    throw new RuntimeException(sprintf('Unterminated Redoc JSON object in "%s".', $source));
}

/**
 * @param array<string, mixed> $document
 * @return list<array{method: string, path: string, operation: array<string, mixed>}>
 */
function contractOperations(array $document): array
{
    $result = [];
    $paths = $document['paths'] ?? [];
    if (!is_array($paths)) {
        return [];
    }

    foreach ($paths as $path => $pathItem) {
        if (!is_string($path) || !is_array($pathItem)) {
            continue;
        }
        foreach ($pathItem as $method => $operation) {
            if (!is_string($method) || !in_array(strtolower($method), CONTRACT_HTTP_METHODS, true) || !is_array($operation)) {
                continue;
            }
            $result[] = [
                'method' => strtoupper($method),
                'path' => $path,
                'operation' => $operation,
            ];
        }
    }

    usort($result, static fn (array $left, array $right): int => [$left['path'], $left['method']] <=> [$right['path'], $right['method']]);
    return $result;
}

/** @param callable(): void $callback */
function contractRun(callable $callback): never
{
    try {
        $callback();
        exit(0);
    } catch (Throwable $throwable) {
        fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
        exit(1);
    }
}
