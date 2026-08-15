# OAuth 2.0 and token lifecycle

The SDK supports Authorization Code with PKCE, Client Credentials, and refresh-token rotation. Access tokens, refresh tokens, authorization codes, client secrets, OAuth `state`, and PKCE verifiers are credentials. Keep them out of URLs, browser storage, exceptions, metrics, and logs.

## Choose the grant and scopes

Use Authorization Code with PKCE when a user grants an integration access to a merchant account. Use Client Credentials only when the integration acts for its own store and that upstream authorization model is permitted. Request the smallest useful scope set; `OAuthScope::all()` is convenient for local exploration, not a production default.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthScope;

$readOnlyScopes = [
    OAuthScope::OpenId,
    OAuthScope::CategoriesRead,
    OAuthScope::OffersRead,
    OAuthScope::OrdersRead,
];
```

## Authorization Code with PKCE

Create the request on the server, then store the returned `state`, `codeVerifier`, and exact `redirectUri` in protected, short-lived server-side state. Tie that record to the initiating browser session and consume it once.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;

$environment = Environment::stage();
$http = SymfonyHttpClientFactory::create();
$oauth = new OAuthClient(
    $environment,
    $http->httpClient,
    $http->requestFactory,
    $http->streamFactory,
);

$request = $oauth->createAuthorizationRequest(
    clientId: 'client-id',
    redirectUri: 'https://app.example.invalid/oauth/callback',
    scopes: [OAuthScope::OpenId, OAuthScope::OrdersRead],
);

// Persist the three values server-side before redirecting the browser.
$authorizationUrl = $request->authorizationUrl;
$expectedState = $request->state;
$codeVerifier = $request->codeVerifier;
$expectedRedirectUri = $request->redirectUri;
```

On the callback, reject an upstream `error` first, load and consume the retained record, then verify both callback values before exchanging the one-time code:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;

OAuthClient::assertAuthorizationCallback(
    expectedState: $expectedState,
    receivedState: $receivedState,
    expectedRedirectUri: $expectedRedirectUri,
    receivedRedirectUri: $receivedRedirectUri,
);

$tokens = $oauth->exchangeAuthorizationCode(
    clientId: $clientId,
    authorizationCode: $authorizationCode,
    redirectUri: $expectedRedirectUri,
    codeVerifier: $codeVerifier,
    clientSecret: $clientSecret, // Omit when the registered client does not require it.
);
```

The SDK always uses PKCE `S256` and validates HTTPS redirect URIs; loopback HTTP is allowed for local development. Routing, session expiry, replay protection, callback error handling, and associating tokens with the correct merchant remain application responsibilities.

## Client Credentials

Read the client secret from a protected runtime secret store and request only the scopes required by the worker:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthScope;

$tokens = $oauth->requestClientCredentialsToken(
    clientId: $clientId,
    clientSecret: $clientSecret,
    scopes: [OAuthScope::OffersRead, OAuthScope::OrdersRead],
);
```

Do not print or serialize the returned `TokenSet` into an application log.

## Store and refresh tokens

`TokenSet` is immutable and contains the access token, optional refresh token, token type, effective scopes, and receipt time. The SDK deliberately provides interfaces rather than a database-backed store. Implement `TokenStoreInterface` so `save()` atomically replaces the entire set: saving only the new access token can lose a rotated refresh token.

For multi-process deployments, implement `LockInterface` with the same shared coordination boundary as the token store. `RefreshingTokenProvider` first reads the token, then—when it is within the default 30-second expiry leeway—locks the `TokenContext`, reloads, refreshes once, and atomically saves the result.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\RefreshingTokenProvider;
use DevLancer\VonHalsky\Auth\SystemClock;
use DevLancer\VonHalsky\Auth\TokenContext;

$context = TokenContext::forEnvironment(
    environment: $environment,
    clientId: $clientId,
    subject: 'merchant-account-id',
    organizationId: 'organization-id',
);

$tokenStore->save($context, $tokens);

$provider = new RefreshingTokenProvider(
    context: $context,
    store: $tokenStore,
    lock: $lock,
    oauthClient: $oauth,
    clientSecret: $clientSecret,
    clock: new SystemClock(),
);
```

During refresh, the SDK retains the old refresh token and scopes if the token endpoint omits replacements; if it returns replacements, the new values win. An absent or expired refresh token causes `AuthenticationFlowException` and requires a new authorization flow. `TokenContext::storageKey()` is a non-reversible namespace key, not an access-control mechanism.

OAuth-flow failures are deliberately redacted `AuthenticationFlowException`s. A successful OAuth flow followed by an API HTTP 401 is instead reported as `AuthenticationException`; distinguish those cases in monitoring.
