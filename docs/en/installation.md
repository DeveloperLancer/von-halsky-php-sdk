# Installation and first client

The SDK requires PHP 8.1+, Composer 2, and the JSON extension. It is a library: it does not require the Symfony Framework, a database, or a queue.

## Install the released package

After a tagged release is published to Packagist, install it in an application:

```bash
composer require dev-lancer/von-halsky-php-sdk
```

The repository currently represents an unreleased development snapshot. For development, clone it and install the locked toolchain:

```bash
git clone https://github.com/DeveloperLancer/von-halsky-php-sdk.git
cd von-halsky-php-sdk
composer install
```

## Create a client with an existing token

`VonHalskyClient::create()` chooses the secure Symfony PSR-18 transport. Supply `Environment::stage()` while developing and `Environment::production()` only for production traffic.

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\VonHalskyClient;

$token = new AccessToken('replace-with-a-token-from-your-secret-store', new DateTimeImmutable('+5 minutes'));
$client = VonHalskyClient::create(new StaticTokenProvider($token), Environment::stage());

$organizations = $client->organizations()->list()->data;
```

The placeholder token is intentional. Do not put a real token in source control, an exception, or application logs. For acquisition and rotation, read [OAuth 2.0 and token lifecycle](./authentication.md).

## Select an organization

Most business operations are organization-scoped. List the authorized organizations, persist the selected ID in application state, and create an immutable context for a call:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** @var VonHalskyClient $client */
$organization = $client->forOrganization(OrganizationId::fromString('organization-id'));
$orders = $organization->orders()->list()->data;
```

The context does not mutate `$client`; it is safe to retain separate contexts for separate organizations. Continue with [client, environments, and organization contexts](./client-and-environments.md).
