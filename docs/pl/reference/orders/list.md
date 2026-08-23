# `OrdersResource::list()`

Zwraca jedną stronę zamówień organizacji.

## Użycie

- Zakres: organizacja.
- Sygnatura: `list(?OrderListOptions $options = null): ApiResponse<PageResult<OrderDetails>>`.
- Parametry: statusy, statusy płatności, limit `0–30`, `offset`, sortowanie, znacznik czasu UTC i język.

## Zachowanie

SDK pobiera jedną stronę. Trwały znacznik czasu UTC zapisuj dopiero po przetworzeniu wyników; zamówienia mogą zawierać dane osobowe.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->orders()->list(new OrderListOptions(paymentStatuses: ['PAID'], limit: 30))->data;
```
