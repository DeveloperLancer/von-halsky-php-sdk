# Instalacja i pierwszy klient

SDK wymaga PHP 8.1+, Composer 2 i rozszerzenia JSON. Nie wymaga całego frameworka Symfony, bazy danych ani kolejki.

Po opublikowaniu oznaczonej wersji pakiet będzie instalowany przez:

```bash
composer require dev-lancer/von-halsky-php-sdk
```

Obecne repozytorium jest nieopublikowaną migawką rozwojową. Do pracy nad nim sklonuj repozytorium i uruchom `composer install`.

## Pierwsze wywołanie

`VonHalskyClient::create()` tworzy bezpieczny domyślny transport Symfony. Na początku używaj `Environment::stage()`.

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\VonHalskyClient;

$token = new AccessToken('token-z-bezpiecznego-magazynu', new DateTimeImmutable('+5 minutes'));
$client = VonHalskyClient::create(new StaticTokenProvider($token), Environment::stage());
$organizacje = $client->organizations()->list()->data;
```

Przykładowy token nie może być rzeczywistym sekretem. Nie zapisuj tokenów, sekretów klientów ani danych klientów w kodzie, logach lub wyjątkach. Po wybraniu organizacji przejdź do [kontekstu organizacji](./klient-i-srodowiska.md).
