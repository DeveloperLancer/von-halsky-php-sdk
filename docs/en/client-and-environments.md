# Client, environments, and organization contexts

`VonHalskyClient` is the immutable entry point to the SDK. It combines one `Environment`, a `TokenProviderInterface`, and PSR HTTP dependencies. Resource methods make one HTTP call and return a typed `ApiResponse`; they do not retain selected organizations or application state.

## Environments

Use one of the predefined immutable environments:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Environment\Environment;

$stage = Environment::stage();
$production = Environment::production();
```

### Obtaining access to Stage

1. Create an account in the [Stage Merchant portal](https://stage-merchant.inpost-group.com/).
2. Email [dok.onboarding@inpost.pl](mailto:dok.onboarding@inpost.pl) and request access to the test environment.
3. Include the email address used to create the account and the details of the store that the test environment should cover, in particular its name and Polish tax identification number.

Each factory supplies the API, authorization, and token URLs as one atomic set. `Environment::custom()` is intended for a local mock server or an explicitly configured proxy. It requires absolute HTTPS URLs, except for loopback HTTP; userinfo, query strings, and fragments are rejected.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Environment\Environment;

$local = Environment::custom(
    id: 'local-contract-test',
    apiBaseUrl: 'http://127.0.0.1:8080/inpsa',
    authorizationUrl: 'http://127.0.0.1:8080/oauth2/authorize',
    tokenUrl: 'http://127.0.0.1:8080/oauth2/token',
);
```

The custom ID is part of `TokenContext`; never reuse one ID for endpoints with different security boundaries. `VonHalskyClient::create()` defaults to production when the environment is omitted, so pass the environment explicitly in development, tests, and worker configuration.

## Default and custom transports

`VonHalskyClient::create()` builds the default Symfony PSR-18 client, PSR-17 request factory, and stream factory. It verifies TLS peers and hosts, disables redirects, and uses a 30-second timeout unless another timeout is supplied.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\TokenProviderInterface;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\VonHalskyClient;

/** @var TokenProviderInterface $tokenProvider */
$client = VonHalskyClient::create($tokenProvider, Environment::stage(), timeout: 15.0);
```

For another PSR-18 implementation, construct `HttpClientDependencies` with a PSR-18 client and PSR-17 request and stream factories, then pass it to `new VonHalskyClient($environment, $tokenProvider, $http)`. The optional Guzzle factory is available only after adding `guzzlehttp/guzzle`; calling it without the package raises `MissingOptionalDependencyException`. Preserve TLS verification, disabled redirects, bounded connect/read timeouts, and a single retry layer in any custom transport.

## Organization scope

`organizations()`, `categories()`, offer deposit types, order delivery methods, and claim types are global. All calls that act on a merchant’s offers, attachments, orders, returns, or claims require an `OrganizationContext`.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$shop = $client->forOrganization(OrganizationId::fromString('organization-id'));
$offers = $shop->offers();
$orders = $shop->orders();
```

`forOrganization()` returns a new object and never changes the original client. Calling an organization-scoped method on a global `OffersResource`, `OrdersResource`, or `ClaimsResource` raises `InvalidRequestException` before any request is sent.

## Language and streams

Most resource methods accept an optional `ResponseLanguage`; the SDK sends it as `Accept-Language`. Attachments are stream-first: callers own streams passed to `upload()` and returned by `download()`. See [attachments](./reference/attachments/README.md) and [reliability](./reliability.md).
