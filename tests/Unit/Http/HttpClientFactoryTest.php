<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Http;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Exception\MissingOptionalDependencyException;
use DevLancer\VonHalsky\Http\GuzzleHttpClientFactory;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Internal\Http\SdkRetryingClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpClientFactoryTest extends TestCase
{
    public function testCreatesDefaultPsrDependencySet(): void
    {
        $dependencies = SymfonyHttpClientFactory::create(5.0);

        self::assertSame(Psr18Client::class, $dependencies->httpClient::class);
        self::assertSame(Psr17Factory::class, $dependencies->requestFactory::class);
        self::assertSame(Psr17Factory::class, $dependencies->streamFactory::class);
        self::assertFalse($dependencies->performsRetries);
    }

    public function testRetryIsExplicitAndCannotBeEnabledTwice(): void
    {
        $dependencies = SymfonyHttpClientFactory::create()->withRetry();

        self::assertInstanceOf(SdkRetryingClientInterface::class, $dependencies->httpClient);
        self::assertTrue($dependencies->performsRetries);

        $this->expectException(ConfigurationException::class);
        $dependencies->withRetry();
    }

    public function testRejectsInvalidTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        SymfonyHttpClientFactory::create(INF);
    }

    public function testSymfonyPsr18TreatsErrorStatusAsResponse(): void
    {
        $factory = new Psr17Factory();
        $client = new Psr18Client(
            new MockHttpClient(new MockResponse('{"error":"invalid"}', ['http_code' => 401])),
            $factory,
            $factory,
        );

        $response = $client->sendRequest($factory->createRequest('GET', 'https://example.test'));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testGuzzleIsOptionalAndCheckedOnlyWhenFactoryIsCalled(): void
    {
        if (class_exists(\GuzzleHttp\Client::class) && class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            $dependencies = GuzzleHttpClientFactory::create();
            self::assertSame(\GuzzleHttp\Client::class, $dependencies->httpClient::class);

            return;
        }

        try {
            GuzzleHttpClientFactory::create();
            self::fail('Expected the missing optional dependency exception.');
        } catch (MissingOptionalDependencyException $exception) {
            self::assertSame('guzzlehttp/guzzle', $exception->package);
            self::assertStringContainsString('composer require guzzlehttp/guzzle', $exception->getMessage());
        }
    }
}
