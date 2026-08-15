# `DeprecatedResource::refuseOrder()`

Wysyła starą komendę odrzucenia zamówienia v1.

## Użycie

- Zakres: organizacja, `$shop->deprecated()`.
- Sygnatura: `refuseOrder(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Wynik: `OrderCommand`.

## Zachowanie

Metoda jest przestarzała i planowana do usunięcia w SDK 2.0; bieżący kontrakt nie opisuje następcy. POST zmienia stan i nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$komenda = $shop->deprecated()->refuseOrder(OrderId::fromString('order-id'))->data;
```
