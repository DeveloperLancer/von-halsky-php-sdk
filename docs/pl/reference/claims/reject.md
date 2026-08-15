# `ClaimsResource::reject()`

Odrzuca reklamację.

## Użycie

- Zakres: organizacja.
- Sygnatura: `reject(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Parametry: ID zamówienia i reklamacji oraz opcjonalny opis do 1000 bajtów.

## Zachowanie

To widoczna dla klienta operacja POST bez automatycznego ponawiania. Zapisz wykonawcę i uzasadnienie w dzienniku audytowym aplikacji.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$wynik = $shop->claims()->reject($orderId, $claimId)->data;
```
