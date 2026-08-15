# `OrdersResource::get()`

Zwraca jedno zamówienie organizacji.

## Użycie

- Zakres: organizacja.
- Sygnatura: `get(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderDetails>`.
- Wynik: `OrderDetails`.

## Zachowanie

Model może zawierać dane osobowe; przechowuj i loguj wyłącznie pola potrzebne do integracji.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$zamowienie = $shop->orders()->get(OrderId::fromString('order-id'))->data;
```
