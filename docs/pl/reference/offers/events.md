# `OffersResource::events()`

Pobiera jedną stronę zdarzeń ofert, od najnowszych.

## Użycie

- Zakres: organizacja.
- Sygnatura: `events(?OfferEventsOptions $options = null): ApiResponse<list<OfferEvent>>`.
- Parametry: `untilId`, typy, limit `0–1000`, język.

## Zachowanie

`untilId` wyklucza wskazane zdarzenie i nowsze. Nie jest kursorem skierowanym w przyszłość; po luce retencji trzeba uzgodnić dane z listą ofert. Nie zakładaj stałego czasu retencji: nie jest on potwierdzoną gwarancją API.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OfferEventsOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$events = $shop->offers()->events(new OfferEventsOptions(limit: 100))->data;
```
