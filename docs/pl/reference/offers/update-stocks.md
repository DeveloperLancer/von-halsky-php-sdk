# `OffersResource::updateStocks()`

Wysyła zbiorczą aktualizację stanów magazynowych.

## Użycie

- Zakres: organizacja.
- Sygnatura: `updateStocks(list<OfferStockUpdate> $updates, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parametry: niepusta lista ID i `Stock`.

## Zachowanie

Pusta lista jest odrzucona lokalnie, a `Stock` waliduje liczbę. PATCH nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$commands = $shop->offers()->updateStocks([new OfferStockUpdate(OfferId::fromString('offer-id'), new Stock(25))])->data;
```
