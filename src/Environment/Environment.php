<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Environment;

use DevLancer\VonHalsky\Exception\ConfigurationException;

/** Immutable, atomic set of API and OAuth endpoints. */
final class Environment
{
    private function __construct(
        public readonly string $id,
        public readonly string $apiBaseUrl,
        public readonly string $authorizationUrl,
        public readonly string $tokenUrl,
    ) {
    }

    public static function stage(): self
    {
        return new self(
            'stage',
            'https://stage-api.inpost-group.com/inpsa',
            'https://stage-account.inpost-group.com/oauth2/authorize',
            'https://stage-account.inpost-group.com/oauth2/token',
        );
    }

    public static function production(): self
    {
        return new self(
            'production',
            'https://api.inpost-group.com/inpsa',
            'https://account.inpost-group.com/oauth2/authorize',
            'https://account.inpost-group.com/oauth2/token',
        );
    }

    public static function custom(
        string $id,
        string $apiBaseUrl,
        string $authorizationUrl,
        string $tokenUrl,
    ): self {
        if (preg_match('/\A[a-z0-9][a-z0-9._-]{0,63}\z/D', $id) !== 1) {
            throw new ConfigurationException(
                'The custom environment ID must contain 1-64 lowercase ASCII letters, digits, dots, underscores or hyphens.',
            );
        }
        if ($id === 'stage' || $id === 'production') {
            throw new ConfigurationException('A custom environment cannot use a reserved official environment ID.');
        }

        return new self(
            $id,
            self::validateUrl($apiBaseUrl, 'API base URL', true),
            self::validateUrl($authorizationUrl, 'authorization URL', false),
            self::validateUrl($tokenUrl, 'token URL', false),
        );
    }

    private static function validateUrl(string $url, string $label, bool $trimTrailingSlash): string
    {
        $parts = parse_url($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
        ) {
            throw new ConfigurationException(sprintf('The %s must be an absolute URL.', $label));
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new ConfigurationException(sprintf('The %s cannot contain userinfo or a fragment.', $label));
        }

        if (isset($parts['query'])) {
            throw new ConfigurationException(sprintf('The %s cannot contain a query string.', $label));
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        if ($scheme !== 'https' && !($scheme === 'http' && self::isLoopback($host))) {
            throw new ConfigurationException(sprintf(
                'The %s must use HTTPS; HTTP is allowed only for a loopback host.',
                $label,
            ));
        }

        return $trimTrailingSlash ? rtrim($url, '/') : $url;
    }

    private static function isLoopback(string $host): bool
    {
        $host = trim($host, '[]');

        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        $packed = @inet_pton($host);

        return $packed !== false
            && strlen($packed) === 4
            && ord($packed[0]) === 127;
    }
}
