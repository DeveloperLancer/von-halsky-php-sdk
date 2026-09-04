# `OffersResource::patch()`

Wykonuje merge patch jednej oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `patch(OfferId $offerId, PatchOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Parametry: ID oraz opakowane w `OptionalValue` wszystkie obsługiwane pola PATCH: ID zewnętrzne, produkt, cena, stan, GPSR, czas wysyłki, URL afiliacyjny, obrazy i polityki posprzedażowe.
- Wynik: zaktualizowane `OfferDetails`.

## Zachowanie

`OptionalValue::undefined()`, `null()` i `of()` oznaczają odpowiednio pominięcie, JSON `null` i wartość. To rozróżnienie obowiązuje także wewnątrz `ProductPatch`, `ProductDimensionsPatch`, `PostSalePatch` i `PostSalePolicyPatch`, więc zmiana jednego pola zagnieżdżonego nie nadpisuje pozostałych. `null()` jest dostępne tylko dla pól, których typ dopuszcza usunięcie wartości.

`product`, `price` i `stock` są wymaganymi elementami istniejącej oferty, dlatego można je pominąć albo zaktualizować, ale nie usunąć przez `null`. Ta sama reguła dotyczy wymaganych pól produktu: `name`, `description`, `brand` i `categoryId`. Limity tekstu są takie same jak przy tworzeniu produktu: nazwa 7–150 znaków, opis 100–100000, marka 1–100, a model i supermodel 1–100, jeśli zostały podane.

`externalId` i `product.ean` można pominąć albo przypisać im wartość różną od `null`; SDK lokalnie odrzuca `null`. API pozwala przypisać każdy z tych identyfikatorów wyłącznie wtedy, gdy nie był wcześniej ustawiony. SDK nie wykonuje dodatkowego odczytu, więc ostatecznie weryfikuje to serwer. `affiliationProductUrl` ma limit 2048 znaków. Gdy do `ProductPatch` przekażesz pobraną `Category`, SDK wymaga kategorii-liścia; dla `CategoryId` ufa wywołującemu, a walidacja należy do serwera.

Zmiany produktu służą przede wszystkim poprawianiu nieprawidłowych ofert w stanie `PENDING`. Dla ofert `PUBLISHED` wytyczne integracyjne zalecają zasadniczo aktualizowanie ceny i stanu; serwer może odrzucić albo ponownie zweryfikować istotną zmianę danych produktu. SDK nie zmienia automatycznie statusu oferty.

PATCH nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\ProductPatch;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->patch(OfferId::fromString('offer-id'), new PatchOfferRequest(
    product: OptionalValue::of(new ProductPatch(
        name: OptionalValue::of('Zmieniona nazwa produktu'),
        description: OptionalValue::of('Zmieniony opis produktu zawierający kompletne i aktualne informacje wymagane przez serwis, przygotowane dla kupującego.'),
        categoryId: OptionalValue::of(CategoryId::fromString('leaf-category-id')),
    )),
))->data;
```

To zmienia tylko trzy wskazane pola produktu. Przykład jest przeznaczony przede wszystkim do poprawy oferty `PENDING`; nie zamyka, nie odtwarza ani nie otwiera ponownie oferty.
