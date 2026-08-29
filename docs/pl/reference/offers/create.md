# `OffersResource::create()`

Wysyła żądanie utworzenia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: `CommandHandle` i proponowane ID oferty.

## Zachowanie

`ProductProposal` wymaga `leaf category` oraz EAN albo MPN; nazwa produktu ma `7–150`, a opis `100–100000` znaków. SKU ma najwyżej 100 znaków. Oferta wymaga od 1 do 20 grafik o nazwach kończących się na `.jpg`, `.png` lub `.webp`; `daysToShip` mieści się w `0–60`. Dla danych GPSR przekazywanych przez `GpsrInfo::required()` SDK wymaga nazwy, adresu i poprawnego e-maila producenta; obsługuje też telefon, osobę odpowiedzialną, numer partii i oznaczenie CE. Gdy przekażesz pełny `Category`, SDK lokalnie sprawdzi, czy jest to `leaf category`; dla samego `CategoryId` ufa wywołującemu. HTTP 201 oznacza przyjęcie `CommandHandle`, nie gotową ofertę. POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$command = $shop->offers()->create($request)->data;
```
