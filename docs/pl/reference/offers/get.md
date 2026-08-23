# `OffersResource::get()`

Zwraca jedną ofertę organizacji.

## Użycie

- Zakres: organizacja.
- Sygnatura: `get(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Wynik: `OfferDetails`.

## Zachowanie

Pusta odpowiedź sukcesu nie jest poprawną ofertą i powoduje `ResponseMappingException`.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->get(OfferId::fromString('offer-id'))->data;
```
