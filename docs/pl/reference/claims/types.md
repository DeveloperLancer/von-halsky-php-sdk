# `ClaimsResource::types()`

Zwraca globalny, lokalizowany słownik typów reklamacji.

## Użycie

- Zakres: globalny, `$client->claims()`.
- Sygnatura: `types(?ResponseLanguage $language = null): ApiResponse<list<ClaimType>>`.
- Wynik: ID i nazwy typów.

## Zachowanie

To jedyna metoda reklamacji bez organizacji.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$types = $client->claims()->types()->data;
```
