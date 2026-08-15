# `OffersResource::updatePrices()`

Wysyła zbiorczą aktualizację cen ofert.

## Użycie

- Zakres: organizacja.
- Sygnatura: `updatePrices(list<OfferPriceUpdate> $updates, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parametry: niepusta lista ID oferty i `Money`.

## Zachowanie

Pusta lista powoduje `InvalidRequestException`. Wyniki są poleceniami, a PATCH nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$komendy = $shop->offers()->updatePrices([new OfferPriceUpdate(OfferId::fromString('offer-id'), Money::fromDecimal('44.99'))])->data;
```
