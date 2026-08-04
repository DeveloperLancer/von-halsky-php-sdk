<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/** Creates the secure default PSR-18/PSR-17 dependency set. */
final class SymfonyHttpClientFactory
{
    public static function create(float $timeout = 30.0): HttpClientDependencies
    {
        self::assertTimeout($timeout);

        $messages = new Psr17Factory();
        $client = HttpClient::create([
            'max_duration' => $timeout,
            'max_redirects' => 0,
            'timeout' => $timeout,
            'verify_host' => true,
            'verify_peer' => true,
        ]);

        return new HttpClientDependencies(
            new Psr18Client($client, $messages, $messages),
            $messages,
            $messages,
        );
    }

    private static function assertTimeout(float $timeout): void
    {
        if (!is_finite($timeout) || $timeout <= 0.0) {
            throw new ConfigurationException('The HTTP timeout must be a finite number greater than zero.');
        }
    }
}
