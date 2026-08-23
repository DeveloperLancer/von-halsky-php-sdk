# `OffersResource::updateAttributes()`

Wysyła uporządkowane operacje atrybutów jednej oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `updateAttributes(OfferId $offerId, OfferAttributesPatch $patch, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parametry: ID oraz niepusta lista `UpsertAttribute`/`RemoveAttribute`.

## Zachowanie

Kolejność jest zachowana; dla powtarzającego się atrybutu API stosuje ostatnią operację. PATCH nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\RemoveAttribute;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->updateAttributes(OfferId::fromString('offer-id'), new OfferAttributesPatch([new RemoveAttribute('attribute-id')]))->data;
```
