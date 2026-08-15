# `DeprecatedResource::deliveryMethods()`

Zwraca stare globalne kody metod dostawy v1.

## Użycie

- Zakres: globalny, `$client->deprecated()`.
- Sygnatura: `deliveryMethods(?ResponseLanguage $language = null): ApiResponse<list<string>>`.
- Wynik: same stare stringowe kody.

## Zachowanie

Metoda jest przestarzała i planowana do usunięcia w SDK 2.0. Dla nowych integracji użyj `OrdersResource::deliveryMethods()` zwracającego pary kod/nazwa v2.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$stareKody = $client->deprecated()->deliveryMethods()->data;
```
