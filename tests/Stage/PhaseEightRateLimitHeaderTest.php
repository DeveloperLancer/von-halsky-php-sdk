<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseEightRateLimitHeaderTest extends StageTestCase
{
    public function testSuccessfulResponsesExposeRateLimitOrRetryHeadersWhenPresent(): void
    {
        $response = $this->stageClient()->categories()->list(new CategoryTreeOptions(depth: 1));
        self::assertSame(200, $response->statusCode);
        $limit = $response->rateLimit;
        if ($limit === null) {
            self::assertFalse($response->headers->has('X-RateLimit-Reset'));
            self::assertFalse($response->headers->has('Retry-After'));
            self::addToAssertionCount(1);

            return;
        }

        if ($limit->resetAt !== null) {
            self::assertSame('UTC', $limit->resetAt->getTimezone()->getName());
        }
        if ($limit->retryAfterSeconds !== null) {
            self::assertGreaterThanOrEqual(0, $limit->retryAfterSeconds);
        }
        self::addToAssertionCount(1);
    }
}
