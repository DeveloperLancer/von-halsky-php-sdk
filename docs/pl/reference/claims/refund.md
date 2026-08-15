# `ClaimsResource::refund()`

Wysyła rozstrzygnięcie reklamacji obejmujące zwrot płatności.

## Użycie

- Zakres: organizacja.
- Sygnatura: `refund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Wynik: `ActionResult`.

## Zachowanie

To operacja o skutkach finansowych. Potwierdź bieżący stan, uprawnienia i decyzję biznesową; POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$wynik = $shop->claims()->refund($orderId, $claimId)->data;
```
