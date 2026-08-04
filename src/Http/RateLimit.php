<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;

/** Rate-limit metadata reported for an API response. */
final class RateLimit
{
    public function __construct(
        public readonly ?int $limit,
        public readonly ?int $remaining,
        public readonly ?DateTimeImmutable $resetAt,
        public readonly ?DateTimeImmutable $retryAt,
        public readonly ?int $retryAfterSeconds,
    ) {
    }

    public static function fromResponse(ResponseInterface $response, ?DateTimeImmutable $now = null): ?self
    {
        $limit = self::unsignedInteger($response->getHeaderLine('X-RateLimit-Limit'));
        $remaining = self::unsignedInteger($response->getHeaderLine('X-RateLimit-Remaining'));
        $reset = self::unsignedInteger($response->getHeaderLine('X-RateLimit-Reset'));
        $retry = trim($response->getHeaderLine('Retry-After'));
        if ($limit === null && $remaining === null && $reset === null && $retry === '') {
            return null;
        }

        $utc = new DateTimeZone('UTC');
        $resetAt = $reset === null ? null : (new DateTimeImmutable('@' . $reset))->setTimezone($utc);
        $retrySeconds = self::unsignedInteger($retry);
        $retryAt = null;
        if ($retrySeconds !== null) {
            $base = ($now ?? new DateTimeImmutable('now', $utc))->setTimezone($utc);
            $retryAt = $base->add(new DateInterval(sprintf('PT%dS', $retrySeconds)));
        } elseif ($retry !== '') {
            $parsed = DateTimeImmutable::createFromFormat(DATE_RFC7231, $retry, $utc);
            if ($parsed !== false) {
                $retryAt = $parsed->setTimezone($utc);
            }
        }

        return new self($limit, $remaining, $resetAt, $retryAt, $retrySeconds);
    }

    private static function unsignedInteger(string $value): ?int
    {
        $value = trim($value);

        return preg_match('/^(?:0|[1-9][0-9]*)$/D', $value) === 1 ? (int) $value : null;
    }
}
