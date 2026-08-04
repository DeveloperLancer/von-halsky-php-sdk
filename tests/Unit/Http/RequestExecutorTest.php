<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Http;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\InvalidTransportRequestException;
use DevLancer\VonHalsky\Exception\NetworkTransportException;
use DevLancer\VonHalsky\Exception\TransportException;
use DevLancer\VonHalsky\Http\Body\MultipartPart;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Tests\Support\FakeClientException;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\Tests\Support\FakeNetworkException;
use DevLancer\VonHalsky\Tests\Support\FakeRequestException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

final class RequestExecutorTest extends TestCase
{
    public function testBuildsAbsoluteRfc3986GetRequest(): void
    {
        [$executor, $client] = $this->executor(new Response(204));

        $response = $executor->execute(
            'GET',
            '/v1/offers',
            ['filter' => 'spaces and/slash', 'page' => 2, 'tag' => ['a+b', 'żółć']],
            ['X-Trace' => ['first', 'second']],
        );

        self::assertSame(204, $response->getStatusCode());
        $request = $client->requests()[0];
        self::assertSame(
            'https://stage-api.inpost-group.com/inpsa/v1/offers?filter=spaces%20and%2Fslash&page=2&tag%5B0%5D=a%2Bb&tag%5B1%5D=%C5%BC%C3%B3%C5%82%C4%87',
            (string) $request->getUri(),
        );
        self::assertSame(['application/json'], $request->getHeader('Accept'));
        self::assertSame(['first', 'second'], $request->getHeader('X-Trace'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testEncodesExplicitJsonNullAndFormBody(): void
    {
        [$executor, $client] = $this->executor(new Response(), new Response());

        $executor->executeJson('PATCH', '/v1/offers/1', null);
        $executor->executeForm('POST', '/oauth', ['scope' => 'read write', 'enabled' => true]);

        $json = $client->requests()[0];
        self::assertSame('application/json', $json->getHeaderLine('Content-Type'));
        self::assertSame('null', (string) $json->getBody());

        $form = $client->requests()[1];
        self::assertSame('application/x-www-form-urlencoded', $form->getHeaderLine('Content-Type'));
        self::assertSame('scope=read%20write&enabled=1', (string) $form->getBody());
    }

    public function testNormalizesTypedDtoBeforeSendingJson(): void
    {
        [$executor, $client] = $this->executor(new Response(202));
        $dto = new class implements RequestDtoInterface {
            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['title' => OptionalValue::of('Typed'), 'description' => OptionalValue::undefined()];
            }
        };

        $executor->executeDto('PATCH', '/v1/offers/1', $dto);

        self::assertSame('{"title":"Typed"}', (string) $client->requests()[0]->getBody());
    }

    public function testBuildsMultipartWithoutTransportSpecificOptions(): void
    {
        [$executor, $client] = $this->executor(new Response(201));
        $factory = new Psr17Factory();

        $executor->executeMultipart('POST', '/v1/attachments', [
            new MultipartPart('description', $factory->createStream('hello')),
            new MultipartPart('file', $factory->createStream('binary-data'), 'a.txt', ['Content-Type' => 'text/plain']),
        ]);

        $request = $client->requests()[0];
        $contentType = $request->getHeaderLine('Content-Type');
        self::assertStringStartsWith('multipart/form-data; boundary=von-halsky-', $contentType);
        $body = (string) $request->getBody();
        self::assertStringContainsString('name="description"', $body);
        self::assertStringContainsString('name="file"; filename="a.txt"', $body);
        self::assertStringContainsString("Content-Type: text/plain\r\n", $body);
        self::assertStringContainsString('binary-data', $body);
    }

    public function testReturnsErrorResponsesAsOrdinaryPsrResponses(): void
    {
        [$executor] = $this->executor(new Response(422), new Response(500));

        self::assertSame(422, $executor->execute('GET', '/first')->getStatusCode());
        self::assertSame(500, $executor->execute('GET', '/second')->getStatusCode());
    }

    public function testAddsProviderTokenAndReplacesCallerAuthorizationHeader(): void
    {
        $client = new FakeHttpClient([new Response(200)]);
        $factory = new Psr17Factory();
        $provider = new StaticTokenProvider(new AccessToken(
            'provider-token',
            new DateTimeImmutable('2030-01-01T00:05:00+00:00'),
        ));
        $executor = new RequestExecutor(
            Environment::stage(),
            $client,
            $factory,
            $factory,
            $provider,
        );

        $executor->execute('GET', '/protected', [], ['authorization' => 'Bearer caller-token']);

        self::assertSame('Bearer provider-token', $client->requests()[0]->getHeaderLine('Authorization'));
    }

    /** @param class-string<TransportException> $expectedException */
    #[DataProvider('clientExceptionProvider')]
    public function testMapsPsrClientExceptionsWithoutLeakingTheirMessage(
        string $kind,
        string $expectedException,
    ): void {
        $factory = new Psr17Factory();
        $request = $factory->createRequest('GET', 'https://example.test?access_token=secret-token');
        $exception = match ($kind) {
            'network' => new FakeNetworkException($request, 'Authorization: Bearer secret-token'),
            'request' => new FakeRequestException($request, 'client_secret=secret-token'),
            default => new FakeClientException('refresh_token=secret-token'),
        };
        [$executor] = $this->executor($exception);

        try {
            $executor->execute('GET', '/safe', [], ['Authorization' => 'Bearer secret-token']);
            self::fail('Expected a mapped transport exception.');
        } catch (TransportException $mapped) {
            self::assertInstanceOf($expectedException, $mapped);
            self::assertStringNotContainsString('secret-token', $mapped->getMessage());
            self::assertNull($mapped->getPrevious());
        }
    }

    /** @return iterable<string, array{string, class-string<TransportException>}> */
    public static function clientExceptionProvider(): iterable
    {
        yield 'network' => ['network', NetworkTransportException::class];
        yield 'invalid request' => ['request', InvalidTransportRequestException::class];
        yield 'generic client' => ['client', TransportException::class];
    }

    /**
     * @param Response|ClientExceptionInterface ...$results
     * @return array{RequestExecutor, FakeHttpClient}
     */
    private function executor(Response|ClientExceptionInterface ...$results): array
    {
        $client = new FakeHttpClient(array_values($results));
        $factory = new Psr17Factory();

        return [
            new RequestExecutor(Environment::stage(), $client, $factory, $factory),
            $client,
        ];
    }
}
