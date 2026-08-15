# `OrdersResource::deliveryMethods()`

Odczytuje aktualny globalny słownik metod dostawy v2.

## Użycie

- Zakres: globalny, `$client->orders()`.
- Sygnatura: `deliveryMethods(?ResponseLanguage $language = null): ApiResponse<list<DeliveryMethod>>`.
- Wynik: pary kod/nazwa.

## Zachowanie

To aktualny odpowiednik starego słownika v1. Nie wymaga kontekstu organizacji.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$metody = $client->orders()->deliveryMethods()->data;
```
