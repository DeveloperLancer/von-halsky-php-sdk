# `OffersResource::create()`

Wysyła polecenie utworzenia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: przyjęte polecenie i proponowane ID oferty.

## Zachowanie

`ProductProposal` wymaga kategorii-liścia oraz EAN albo MPN; `daysToShip` mieści się w `0–60`. Gdy przekażesz pełny `Category`, SDK sprawdzi liść lokalnie; dla samego `CategoryId` ufa wywołującemu. HTTP 201 oznacza przyjęcie polecenia, nie gotową ofertę. POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$polecenie = $shop->offers()->create($request)->data;
```
