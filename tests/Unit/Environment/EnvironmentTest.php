<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Environment;

use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\ConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testProvidesAtomicOfficialEnvironments(): void
    {
        $stage = Environment::stage();
        self::assertSame('stage', $stage->id);
        self::assertSame('https://stage-api.inpost-group.com/inpsa', $stage->apiBaseUrl);
        self::assertSame('https://stage-account.inpost-group.com/oauth2/authorize', $stage->authorizationUrl);
        self::assertSame('https://stage-account.inpost-group.com/oauth2/token', $stage->tokenUrl);

        $production = Environment::production();
        self::assertSame('production', $production->id);
        self::assertSame('https://api.inpost-group.com/inpsa', $production->apiBaseUrl);
        self::assertSame('https://account.inpost-group.com/oauth2/authorize', $production->authorizationUrl);
        self::assertSame('https://account.inpost-group.com/oauth2/token', $production->tokenUrl);
    }

    public function testAllowsHttpsAndLoopbackHttpForCustomEnvironment(): void
    {
        $https = Environment::custom(
            'proxy-1',
            'https://proxy.example.test/api/',
            'https://auth.example.test/authorize',
            'https://auth.example.test/token',
        );
        self::assertSame('https://proxy.example.test/api', $https->apiBaseUrl);

        $loopback = Environment::custom(
            'local',
            'http://127.12.34.56:8080/api',
            'http://localhost:8080/authorize',
            'http://[::1]:8080/token',
        );
        self::assertSame('local', $loopback->id);
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testRejectsUnsafeCustomConfiguration(string $id, string $url): void
    {
        $this->expectException(ConfigurationException::class);
        Environment::custom($id, $url, 'https://auth.example.test/authorize', 'https://auth.example.test/token');
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'invalid ID' => ['UPPER CASE', 'https://api.example.test'];
        yield 'reserved ID' => ['stage', 'https://api.example.test'];
        yield 'non-loopback HTTP' => ['custom', 'http://api.example.test'];
        yield 'userinfo' => ['custom', 'https://user:secret@api.example.test'];
        yield 'fragment' => ['custom', 'https://api.example.test/path#fragment'];
        yield 'query' => ['custom', 'https://api.example.test/path?secret=value'];
        yield 'relative URL' => ['custom', '/api'];
    }
}
