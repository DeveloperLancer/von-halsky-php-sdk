# Klient, środowiska i kontekst organizacji

`VonHalskyClient` jest niezmiennym punktem wejścia do SDK. Łączy jedno `Environment`, dostawcę `TokenProviderInterface` i zależności HTTP zgodne z PSR. Metody zasobów wykonują pojedyncze wywołanie i zwracają typowane `ApiResponse`; klient nie przechowuje wybranej organizacji ani stanu procesu biznesowego.

## Środowiska

Korzystaj z gotowych, niezmiennych środowisk:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Environment\Environment;

$stage = Environment::stage();
$production = Environment::production();
```

### Uzyskanie dostępu do Stage

Instrukcję założenia konta i uzyskania dostępu do środowiska Stage znajdziesz w [oficjalnym portalu API InPost](https://inpsa-api-portal.inpost-group.com/).

Każda fabryka dostarcza jeden spójny zestaw adresów API, autoryzacji i tokenów. `Environment::custom()` służy do lokalnego serwera testowego lub jawnie skonfigurowanego serwera pośredniczącego. Wymaga bezwzględnych adresów HTTPS, z wyjątkiem lokalnego loopback URI; dane użytkownika w URL, zapytania i fragmenty są odrzucane.

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

ID środowiska niestandardowego jest częścią `TokenContext`; nie używaj tego samego ID dla adresów o różnych granicach bezpieczeństwa. `VonHalskyClient::create()` domyślnie wybiera produkcję, jeśli pominiesz środowisko, dlatego w rozwoju, testach i konfiguracji procesów zawsze przekazuj je jawnie.

## Transport domyślny i własny

`VonHalskyClient::create()` buduje klienta Symfony zgodnego z PSR-18 oraz fabryki żądań i strumieni PSR-17. Domyślny transport weryfikuje certyfikat i nazwę hosta TLS, nie podąża za przekierowaniami i ma limit czasu 30 sekund.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\TokenProviderInterface;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\VonHalskyClient;

/** @var TokenProviderInterface $tokenProvider */
$client = VonHalskyClient::create(
    $tokenProvider,
    Environment::stage(),
    timeout: 15.0,
);
```

Dla innej implementacji PSR-18 utwórz `HttpClientDependencies` z klientem oraz fabrykami żądań i strumieni PSR-17, a następnie przekaż do `new VonHalskyClient($environment, $tokenProvider, $http)`. Opcjonalna fabryka Guzzle wymaga pakietu `guzzlehttp/guzzle`; bez niego zgłasza `MissingOptionalDependencyException`. Własny transport powinien zachować weryfikację TLS, wyłączone przekierowania, ograniczone czasy połączenia i odczytu oraz tylko jedną warstwę ponawiania.

## Zakres organizacji

`organizations()`, `categories()`, typy depozytów ofert, metody dostawy zamówień i typy reklamacji są globalne. Operacje dotyczące ofert, załączników, zamówień, zwrotów, reklamacji oraz starego odrzucenia zamówienia wymagają `OrganizationContext`.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$shop = $client->forOrganization(OrganizationId::fromString('organization-id'));
$offers = $shop->offers();
$orders = $shop->orders();
```

`forOrganization()` zwraca nowy obiekt i nie zmienia klienta. Możesz równolegle utrzymywać osobne konteksty organizacji, ale przed każdym wyborem sprawdź, czy bieżący podmiot ma do niej dostęp. Wywołanie metody wymagającej organizacji na globalnym zasobie `OffersResource`, `OrdersResource` lub `ClaimsResource` kończy się lokalnym `InvalidRequestException` przed wysłaniem żądania.

## Język i strumienie

Większość metod przyjmuje opcjonalne `ResponseLanguage`; SDK przesyła je jako `Accept-Language`. Załączniki korzystają bezpośrednio ze strumieni: wywołujący odpowiada za zamknięcie strumienia przekazanego do `upload()` oraz zwróconego przez `download()`. Zobacz [referencję załączników](./reference/attachments/README.md) i [niezawodność](./niezawodnosc.md).
