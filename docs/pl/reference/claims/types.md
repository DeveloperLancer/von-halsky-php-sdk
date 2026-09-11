# `ClaimsResource::types()`

Zwraca globalny, lokalizowany słownik typów reklamacji.

## Użycie

- Zakres: globalny, `$client->claims()`.
- Sygnatura: `types(?ResponseLanguage $language = null): ApiResponse<list<ClaimType>>`.
- Wynik: ID i nazwy typów.

## Zachowanie

To jedyna metoda reklamacji bez organizacji. Błędy API i transportu: [wspólna obsługa](../../responses-and-errors.md).

Opublikowany kontrakt OpenAPI 1.6.9 opisuje obiekt JSON `{ "data": [{ "id", "description" }] }`. Żywy Stage (`GET /v1/orders/claim-types`, obserwacja 2026-09-11) zwraca tablicę `[{ "id", "description", "code" }]`. To rozjazd odpowiedzi platformy względem oficjalnej specyfikacji, nie błąd modelu SDK. SDK przyjmuje oba kształty, żeby `types()` działało na Stage i na udokumentowanej kopercie. Dodatkowe pole Stage `code` zostaje w `ClaimType::additionalData()`. Produkcji nie porównywano.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$types = $client->claims()->types()->data;
```
