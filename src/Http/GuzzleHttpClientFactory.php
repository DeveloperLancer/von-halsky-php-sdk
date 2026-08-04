<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http;

use DevLancer\VonHalsky\Exception\ConfigurationException;
use DevLancer\VonHalsky\Exception\MissingOptionalDependencyException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Creates PSR dependencies backed by the optional Guzzle package. */
final class GuzzleHttpClientFactory
{
    public static function create(float $timeout = 30.0): HttpClientDependencies
    {
        if (!is_finite($timeout) || $timeout <= 0.0) {
            throw new ConfigurationException('The HTTP timeout must be a finite number greater than zero.');
        }

        if (!class_exists(\GuzzleHttp\Client::class) || !class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            throw MissingOptionalDependencyException::forPackage(
                'guzzlehttp/guzzle',
                'GuzzleHttpClientFactory',
                'composer require guzzlehttp/guzzle',
            );
        }

        $client = new \GuzzleHttp\Client([
            'allow_redirects' => false,
            'connect_timeout' => $timeout,
            'http_errors' => false,
            'timeout' => $timeout,
            'verify' => true,
        ]);
        $messages = new \GuzzleHttp\Psr7\HttpFactory();

        if (!$client instanceof ClientInterface
            || !$messages instanceof RequestFactoryInterface
            || !$messages instanceof StreamFactoryInterface
        ) {
            throw new ConfigurationException(
                'Installed Guzzle packages do not provide the required PSR-18 and PSR-17 interfaces.',
            );
        }

        return new HttpClientDependencies($client, $messages, $messages);
    }
}
