# `ReturnsResource::forOrder()`

Zwraca zwroty przypisane do zamówienia.

## Użycie

- Zakres: organizacja.
- Sygnatura: `forOrder(OrderId $orderId, ?ReturnListOptions $options = null): ApiResponse<PageResult<ReturnDetails>>`.
- Wynik: strona zwrotów zamówienia.

## Zachowanie

Nawet dla jednego zamówienia wynik pozostaje stronicowany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->returns()->forOrder(OrderId::fromString('order-id'))->data;
```
