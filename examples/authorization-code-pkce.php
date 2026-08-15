<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$clientId = getenv('VON_HALSKY_CLIENT_ID');
if (!is_string($clientId) || $clientId === '') {
    throw new RuntimeException('Set VON_HALSKY_CLIENT_ID before running this example.');
}

$environment = Environment::stage();
$http = SymfonyHttpClientFactory::create();
$oauth = new OAuthClient(
    $environment,
    $http->httpClient,
    $http->requestFactory,
    $http->streamFactory,
);
$authorization = $oauth->createAuthorizationRequest(
    $clientId,
    'https://merchant.example/oauth/callback',
    OAuthScope::all(),
);

// Store state, codeVerifier, and redirectUri in the protected server-side user session.
// Never log or expose codeVerifier. Redirect the browser to authorizationUrl.
fwrite(STDOUT, $authorization->authorizationUrl . PHP_EOL);
