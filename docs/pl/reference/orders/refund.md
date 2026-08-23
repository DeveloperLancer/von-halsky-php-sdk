# `OrdersResource::refund()`

Żąda pełnego lub dokładnego częściowego zwrotu płatności za zamówienie.

## Użycie

- Zakres: organizacja.
- Sygnatura: `refund(OrderId $orderId, ?RefundRequest $request = null, ?ResponseLanguage $language = null): ApiResponse<RefundResult>`.
- Parametry: brak DTO oznacza pełny zwrot płatności; `RefundRequest(Money)` — zwrot częściowy.

## Zachowanie

To operacja finansowa. Przed wywołaniem sprawdź bieżące zamówienie i decyzję biznesową; POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Order\RefundRequest;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->orders()->refund(OrderId::fromString('order-id'), new RefundRequest(Money::fromDecimal('12.34')))->data;
```
