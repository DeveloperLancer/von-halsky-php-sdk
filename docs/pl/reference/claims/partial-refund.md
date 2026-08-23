# `ClaimsResource::partialRefund()`

Wysyła rozstrzygnięcie reklamacji obejmujące częściowy zwrot płatności.

## Użycie

- Zakres: organizacja.
- Sygnatura: `partialRefund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Wynik: `ActionResult`.

## Zachowanie

To decyzja finansowa i widoczna dla klienta; POST nie jest automatycznie ponawiany. Przed wywołaniem sprawdź uprawnienia i stan reklamacji.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$result = $shop->claims()->partialRefund($orderId, $claimId)->data;
```
