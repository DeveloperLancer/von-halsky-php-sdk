<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Serialization;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\AuthenticationException;
use DevLancer\VonHalsky\Exception\AuthorizationException;
use DevLancer\VonHalsky\Exception\BadRequestException;
use DevLancer\VonHalsky\Exception\ConflictException;
use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Exception\RateLimitException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Exception\ServerException;
use DevLancer\VonHalsky\Exception\UnprocessableEntityException;
use DevLancer\VonHalsky\Http\RateLimit;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiExceptionFactoryTest extends TestCase
{
    /** @param class-string<ApiException> $exceptionClass */
    #[DataProvider('statusProvider')]
    public function testMapsEveryDocumentedStatus(int $status, string $exceptionClass): void
    {
        $response = new Response(
            $status,
            ['Content-Type' => 'application/problem+json', 'Authorization' => 'Bearer secret'],
            '{"errorCode":"CODE","errorMessage":"Safe","details":[{"field":"name","message":"Required"}]}',
        );

        try {
            (new JsonResponseDecoder())->decodeObject($response, 'testOperation');
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertInstanceOf($exceptionClass, $exception);
            self::assertSame('CODE', $exception->errorCode);
            self::assertSame('testOperation', $exception->operationId);
            self::assertCount(1, $exception->details);
            self::assertArrayNotHasKey('Authorization', $exception->safeHeaders);
        }
    }

    /** @return iterable<string, array{int, class-string<ApiException>}> */
    public static function statusProvider(): iterable
    {
        yield '400' => [400, BadRequestException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '403' => [403, AuthorizationException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, ConflictException::class];
        yield '422' => [422, UnprocessableEntityException::class];
        yield '429' => [429, RateLimitException::class];
        yield '500' => [500, ServerException::class];
        yield '418' => [418, ApiException::class];
    }

    public function testInvalidProblemIsTruncatedAndRedacted(): void
    {
        $response = new Response(500, [], 'email=test@example.com Authorization: Bearer very-secret-token');

        try {
            (new JsonResponseDecoder())->decodeObject($response, 'broken');
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertNotNull($exception->invalidBodyExcerpt);
            self::assertStringNotContainsString('test@example.com', $exception->invalidBodyExcerpt);
            self::assertStringNotContainsString('very-secret-token', $exception->invalidBodyExcerpt);
        }
    }

    public function testSuccessfulEmptyAndInvalidBodiesAreDistinct(): void
    {
        $decoder = new JsonResponseDecoder();
        self::assertNull($decoder->decodeObject(new Response(204), 'empty'));

        $this->expectException(ResponseMappingException::class);
        $decoder->decodeObject(new Response(200), 'invalid');
    }

    public function testParsesRateLimitDatesAndSeconds(): void
    {
        $rateLimit = RateLimit::fromResponse(new Response(429, [
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => '1785873600',
            'Retry-After' => '15',
        ]), new DateTimeImmutable('2026-08-04T12:00:00Z'));

        self::assertNotNull($rateLimit);
        self::assertSame(100, $rateLimit->limit);
        self::assertSame('2026-08-04T12:00:15+00:00', $rateLimit->retryAt?->format(DATE_ATOM));
    }

    public function testParsesHttpDateRetryAfterAndIgnoresMalformedValues(): void
    {
        $rateLimit = RateLimit::fromResponse(new Response(429, [
            'X-RateLimit-Limit' => '-1',
            'X-RateLimit-Remaining' => 'invalid',
            'Retry-After' => 'Wed, 05 Aug 2026 10:00:30 GMT',
        ]), new DateTimeImmutable('2026-08-05T10:00:00Z'));

        self::assertNotNull($rateLimit);
        self::assertNull($rateLimit->limit);
        self::assertNull($rateLimit->remaining);
        self::assertNull($rateLimit->retryAfterSeconds);
        self::assertSame('2026-08-05T10:00:30+00:00', $rateLimit->retryAt?->format(DATE_ATOM));
    }
}
