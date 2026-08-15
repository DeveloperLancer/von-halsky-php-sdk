# Von Halsky PHP SDK

An independent PHP 8.1+ SDK for the InPost Von Halsky API. It provides typed request objects, immutable response models, OAuth 2.0 helpers, PSR-18 HTTP integration, and resource clients for organizations, catalogue data, offers, attachments, orders, returns, refunds, and claims.

This is not an official InPost product and is not affiliated with or endorsed by InPost. InPost and Von Halsky are trademarks of their respective owners.

## Status

The SDK implements the complete public resource surface recorded in this repository: 42 operations across organizations, categories, offers, attachments, orders, returns, claims, and an explicitly isolated deprecated API. It is not yet published on Packagist, so the repository is a development snapshot rather than a release promise.

## Requirements

- PHP 8.1 or newer;
- Composer 2;
- the JSON extension.

## Install

After the first tagged release is published, install the package with:

```bash
composer require dev-lancer/von-halsky-php-sdk
```

Until then, use a Composer VCS repository or clone this repository for development. See [Installation and first client](./docs/en/installation.md).

## First API call

For an existing access token, create a client with the default secure Symfony transport and list the organizations available to that token:

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\VonHalskyClient;

$client = VonHalskyClient::create(
    new StaticTokenProvider(new AccessToken('access-token', new DateTimeImmutable('+5 minutes'))),
    Environment::stage(),
);

$organizations = $client->organizations()->list()->data;
```

For production integrations, obtain and rotate tokens with the [OAuth guide](./docs/en/authentication.md); never hard-code or log real credentials.

## Documentation

Choose the documentation language: [English](./docs/en/README.md) or [Polish](./docs/pl/README.md). Both versions offer focused reading paths for a first integration, OAuth, catalogue and offers, order and post-sale processing, and production reliability.

- [Guides](./docs/en/README.md#guides) explain how the SDK fits into an application.
- [Operation reference](./docs/en/reference/README.md) documents every public resource method.
- [Production checklist](./docs/en/production-checklist.md) summarizes the application responsibilities that the SDK deliberately leaves to you.
- [Runnable examples](./examples/README.md) demonstrate selected workflows without credentials.

## License

The SDK source code is available under the [MIT License](./LICENSE).
