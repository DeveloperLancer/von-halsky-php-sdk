# `OffersResource::depositTypes()`

Odczytuje globalny słownik depozytów.

## Użycie

- Zakres: globalny, `$client->offers()`.
- Sygnatura: `depositTypes(?ResponseLanguage $language = null): ApiResponse<list<DepositType>>`.
- Wynik: ID, nazwy i ceny `DepositType`.

## Zachowanie

To jedyna metoda ofert bez organizacji. Pozostałe wymagają kontekstu organizacji.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$depositTypes = $client->offers()->depositTypes()->data;
```
