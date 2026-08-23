# Instalacja i pierwszy klient

SDK wymaga PHP 8.1+, Composer 2 i rozszerzenia JSON. Nie wymaga całego frameworka Symfony, bazy danych ani kolejki.

Po opublikowaniu oznaczonej wersji pakiet będzie instalowany przez:

```bash
composer require dev-lancer/von-halsky-php-sdk
```

Pakiet nie został jeszcze opublikowany. Aby pracować z wersją rozwojową, sklonuj repozytorium i zainstaluj zablokowany zestaw zależności:

```bash
git clone https://github.com/DeveloperLancer/von-halsky-php-sdk.git
cd von-halsky-php-sdk
composer install
```

## Pierwsze wywołanie

`VonHalskyClient::create()` tworzy bezpieczny domyślny transport Symfony. Podczas tworzenia integracji używaj `Environment::stage()`; `Environment::production()` wybieraj tylko dla ruchu produkcyjnego.

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

Przykładowy token nie może być rzeczywistym sekretem. Nie zapisuj tokenów, sekretów klientów ani danych klientów w kodzie, logach lub wyjątkach. Sposób pozyskiwania i odświeżania tokenów opisuje [OAuth 2.0 i cykl życia tokenów](./uwierzytelnianie.md).

## Wybór organizacji

Większość operacji biznesowych działa w kontekście organizacji. Odczytaj dozwolone organizacje, zapisz wybrane ID w stanie aplikacji i utwórz niezmienny kontekst dla wywołania:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** @var VonHalskyClient $client */
$organization = $client->forOrganization(OrganizationId::fromString('organization-id'));
$orders = $organization->orders()->list()->data;
```

Kontekst nie zmienia `$client`, więc można bezpiecznie przechowywać osobne konteksty dla różnych organizacji. Następnie przejdź do [klienta, środowisk i kontekstów organizacji](./klient-i-srodowiska.md).
