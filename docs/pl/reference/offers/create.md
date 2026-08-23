# `OffersResource::create()`

Wysyła żądanie utworzenia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: `CommandHandle` i proponowane ID oferty.

## Zachowanie

`ProductProposal` wymaga `leaf category` oraz EAN albo MPN; `daysToShip` mieści się w `0–60`. Gdy przekażesz pełny `Category`, SDK lokalnie sprawdzi, czy jest to `leaf category`; dla samego `CategoryId` ufa wywołującemu. HTTP 201 oznacza przyjęcie `CommandHandle`, nie gotową ofertę. POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$command = $shop->offers()->create($request)->data;
```
