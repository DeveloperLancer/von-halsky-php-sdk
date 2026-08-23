# `OffersResource::patch()`

Wykonuje merge patch jednej oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `patch(OfferId $offerId, PatchOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Wynik: zaktualizowane `OfferDetails`.

## Zachowanie

`OptionalValue::undefined()`, `null()` i `of()` oznaczają odpowiednio pominięcie, JSON `null` i wartość. PATCH nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->patch(OfferId::fromString('offer-id'), new PatchOfferRequest(stock: OptionalValue::null()))->data;
```
