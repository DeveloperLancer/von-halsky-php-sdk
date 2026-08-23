# `OrdersResource::accept()`

Wysyła żądanie akceptacji zamówienia.

## Użycie

- Zakres: organizacja.
- Sygnatura: `accept(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Wynik: `OrderCommand`.

## Zachowanie

Zmiana może być asynchroniczna. Zapisz `commandId` i sprawdź później `command()` lub zdarzenia. POST nie jest automatycznie ponawiany; po niejednoznacznym błędzie sieciowym najpierw odczytaj stan zamówienia.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->orders()->accept(OrderId::fromString('order-id'))->data;
```
