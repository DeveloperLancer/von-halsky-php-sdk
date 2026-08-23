# `ClaimsResource::get()`

Zwraca reklamację w zakresie zamówienia.

## Użycie

- Zakres: organizacja.
- Sygnatura: `get(OrderId $orderId, ClaimId $claimId, ?ResponseLanguage $language = null): ApiResponse<ClaimDetails>`.
- Wynik: `ClaimDetails`.

## Zachowanie

ID zamówienia jest częścią zakresu reklamacji. Przed akcją odczytaj aktualny stan.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ClaimId;
use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$claim = $shop->claims()->get(OrderId::fromString('order-id'), ClaimId::fromString('claim-id'))->data;
```
