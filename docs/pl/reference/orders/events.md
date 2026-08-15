# `OrdersResource::events()`

Pobiera jedną najnowszą stronę zdarzeń zamówień.

## Użycie

- Zakres: organizacja.
- Sygnatura: `events(?OrderEventsOptions $options = null): ApiResponse<list<OrderEvent>>`.
- Parametry: `untilId`, typy, limit `0–1000`, język.

## Zachowanie

`untilId` wyklucza wskazane zdarzenie i nowsze; nie jest kursorem skierowanym w przyszłość. Po luce retencji pobierz listę i uzgodnij stan. Dokładny czas retencji wymaga weryfikacji w Stage.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderEventsOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$zdarzenia = $shop->orders()->events(new OrderEventsOptions(limit: 100))->data;
```
