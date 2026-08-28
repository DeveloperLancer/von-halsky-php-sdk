<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use RuntimeException;

final class StageTestConfig
{
    private const ENVIRONMENT_KEYS = [
        'client_id' => 'VON_HALSKY_STAGE_CLIENT_ID',
        'client_secret' => 'VON_HALSKY_STAGE_CLIENT_SECRET',
        'organization_id' => 'VON_HALSKY_STAGE_ORGANIZATION_ID',
        'leaf_category_id' => 'VON_HALSKY_STAGE_LEAF_CATEGORY_ID',
        'product_ean' => 'VON_HALSKY_STAGE_PRODUCT_EAN',
        'offer_image_url' => 'VON_HALSKY_STAGE_OFFER_IMAGE_URL',
        'command_timeout_seconds' => 'VON_HALSKY_STAGE_COMMAND_TIMEOUT_SECONDS',
        'poll_interval_milliseconds' => 'VON_HALSKY_STAGE_POLL_INTERVAL_MILLISECONDS',
    ];

    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly ?string $organizationId,
        public readonly ?string $leafCategoryId,
        public readonly string $productEan,
        public readonly string $offerImageUrl,
        public readonly int $commandTimeoutSeconds,
        public readonly int $pollIntervalMilliseconds,
    ) {
    }

    public static function load(): self
    {
        $path = __DIR__ . '/stage-config.local.php';
        $values = file_exists($path) ? require $path : [];
        if (!is_array($values) || array_is_list($values)) {
            throw new RuntimeException('The Stage test configuration must return an associative array.');
        }

        foreach (self::ENVIRONMENT_KEYS as $key => $environmentName) {
            $environmentValue = getenv($environmentName);
            if (is_string($environmentValue) && $environmentValue !== '') {
                $values[$key] = $environmentValue;
            }
        }

        $required = ['client_id', 'client_secret', 'product_ean'];
        $missing = [];
        foreach ($required as $key) {
            if (!isset($values[$key]) || !is_string($values[$key]) || $values[$key] === '') {
                $missing[] = $key;
            }
        }
        if ($missing !== []) {
            throw new RuntimeException('Missing Stage test configuration: ' . implode(', ', $missing) . '.');
        }

        $clientId = self::requiredString($values, 'client_id');
        $clientSecret = self::requiredString($values, 'client_secret');
        $organizationId = self::optionalString($values, 'organization_id');
        $leafCategoryId = self::optionalString($values, 'leaf_category_id');
        $productEan = self::requiredString($values, 'product_ean');
        $offerImageUrl = self::optionalString($values, 'offer_image_url')
            ?? 'https://placehold.co/1200x1200/png?text=Von%20Halsky%20Stage%20Test';
        $commandTimeoutSeconds = self::integer($values, 'command_timeout_seconds', 180, 1, 300);
        $pollIntervalMilliseconds = self::integer($values, 'poll_interval_milliseconds', 1000, 100, 5000);
        if ($pollIntervalMilliseconds > $commandTimeoutSeconds * 1000) {
            throw new RuntimeException('poll_interval_milliseconds cannot exceed command_timeout_seconds.');
        }

        return new self(
            $clientId,
            $clientSecret,
            $organizationId,
            $leafCategoryId,
            $productEan,
            $offerImageUrl,
            $commandTimeoutSeconds,
            $pollIntervalMilliseconds,
        );
    }

    /** @param array<array-key, mixed> $values */
    private static function optionalString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new RuntimeException($key . ' must be a string when configured.');
        }

        return $value;
    }

    /** @param array<array-key, mixed> $values */
    private static function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException($key . ' must be a non-empty string.');
        }

        return $value;
    }

    /** @param array<array-key, mixed> $values */
    private static function integer(array $values, string $key, int $default, int $minimum, int $maximum): int
    {
        $value = $values[$key] ?? $default;
        if (is_string($value) && preg_match('/^[0-9]+$/D', $value) === 1) {
            $value = (int) $value;
        }
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            throw new RuntimeException(sprintf('%s must be an integer between %d and %d.', $key, $minimum, $maximum));
        }

        return $value;
    }
}
