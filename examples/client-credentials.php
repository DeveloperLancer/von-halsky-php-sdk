<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$clientId = getenv('VON_HALSKY_CLIENT_ID');
$clientSecret = getenv('VON_HALSKY_CLIENT_SECRET');
if (!is_string($clientId) || $clientId === '' || !is_string($clientSecret) || $clientSecret === '') {
    throw new RuntimeException('Set VON_HALSKY_CLIENT_ID and VON_HALSKY_CLIENT_SECRET before running this example.');
}

// Client Credentials is only for a merchant integrating its own store.
$environment = Environment::stage();
$http = SymfonyHttpClientFactory::create();
$oauth = new OAuthClient(
    $environment,
    $http->httpClient,
    $http->requestFactory,
    $http->streamFactory,
);
$tokens = $oauth->requestClientCredentialsToken($clientId, $clientSecret, OAuthScope::all());

// Do not print or log token values.
fwrite(STDOUT, 'Access token expires at ' . $tokens->accessToken->expiresAt->format(DATE_ATOM) . PHP_EOL);
