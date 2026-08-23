# Zamówienia i obsługa posprzedażowa

Zamówienia, zwroty i reklamacje działają w kontekście organizacji. SDK wykonuje pojedyncze żądania HTTP; nie synchronizuje bazy aplikacji, nie tworzy przesyłek ShipX i nie podejmuje decyzji finansowych ani decyzji obsługi klienta.

## Synchronizacja zamówień bez utraty zmian

`orders()->list()` zwraca jedną stronę opartą na `offset`. Użyj czasu aktualizacji UTC do wyznaczenia granicy przebiegu, zapisz każdą stronę przed przesunięciem `offset` i aktualizuj rekordy po stabilnym ID zamówienia. Nie przesuwaj trwałego `checkpoint` wyłącznie dlatego, że żądanie HTTP się powiodło.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->orders()->list(new OrderListOptions(
    paymentStatuses: ['PAID', 'NOT_PAID'],
    updatedAtGte: UtcDateTime::fromString('2026-01-01T00:00:00Z'),
    sort: ['updatedAt'],
))->data;
```

Synchronizator produkcyjny powinien w jednej transakcji zapisywać granicę przebiegu, ostatni trwały `offset` lub czas oraz własny stan przetwarzania. Ponownie odczytuj nakładający się przedział albo okresowo wykonuj pełne uzgodnienie, aby obsłużyć równe znaczniki czasu, równoległe aktualizacje i wznowienie po awarii. Wielkość nakładającego się przedziału jest decyzją aplikacji i wymaga sprawdzenia w Stage.

Strumienie zdarzeń zamówień i ofert są zwracane od najnowszych. `untilId` prosi o zdarzenia starsze od podanego ID i wyklucza je razem z nowszymi; nie jest kursorem skierowanym w przyszłość. Zapisuj ID ostatniego zdarzenia i czas `checkpoint`, usuwaj duplikaty po ID, a po podejrzeniu luki uzgadniaj stan z autorytatywnymi listami. Nie zakładaj stałego czasu retencji zdarzeń: nie jest on potwierdzoną gwarancją API.

## Operacje asynchroniczne i refundy

`orders()->accept()` zwraca `OrderCommand`. Udana odpowiedź przyjęcia może oznaczać zmianę oczekującą; zapisz `commandId` i później sprawdź `command()` albo odpowiednie zdarzenia. SDK nigdy automatycznie nie ponawia POST, PATCH ani DELETE. Jeżeli zapis zakończy się niejednoznacznym błędem sieciowym, przed kolejną próbą uzgodnij stan zewnętrzny.

Wywołanie `refund($orderId)` bez `RefundRequest` żąda pełnego zwrotu płatności. `new RefundRequest(Money::fromDecimal('10.00'))` żąda dokładnie takiej częściowej kwoty. SDK sprawdza zapis kwoty, ale nie rozstrzyga, czy jest ona poprawna biznesowo, nadal możliwa do zwrotu lub już zwrócona.

## Prywatność i granice integracji

`OrderDetails` ma typowane ID i sumy, lecz `orderLines`, `customer`, `delivery`, `invoice` oraz `paymentDetails` są zgodnymi z przyszłymi zmianami tablicami zagnieżdżonymi. Adres e-mail klienta jest adresem pośredniczącym platformy; dane dostawy i faktury mogą zawierać bezpośrednie dane osobowe. Zwroty i reklamacje mogą również zawierać opisy oraz identyfikatory przekazane przez klienta.

Traktuj cały model jako wrażliwy, nawet jeśli odczytujesz tylko jedno pole typowane:

- nie loguj zserializowanych modeli ani treści wyjątków;
- ogranicz pola kopiowane do analityki, kolejek i integracji ShipX;
- szyfruj przechowywane dane osobowe i stosuj własne reguły retencji;
- sprawdzaj uprawnienie do organizacji przed udostępnieniem danych operatorom;
- w przykładach i testach Stage używaj danych syntetycznych.

SDK nie implementuje tworzenia przesyłek ShipX, etykiet, manifestów ani śledzenia. Skonfiguruj tę integrację osobno i przekazuj tylko wymagane dane dostawy.

## Returns i Claims

`returns` to zwroty towarów: lista, lista dla zamówienia, szczegóły, akceptacja i odrzucenie. `claims` to reklamacje: globalny słownik typów, lista i szczegóły w organizacji oraz odrzucenie, częściowy `refund` i pełny `refund`. Znane SDK stany `claim` to `APPROVED`, `REJECTED` oraz `RESOLUTION_IN_PROGRESS`; model odpowiedzi zachowa przyszłą nieznaną wartość, natomiast filtry listy pozostają tekstem i serwer może je odrzucić.

Operacje posprzedażowe są widoczne dla klienta i mogą mieć skutek finansowy. Przed każdym wywołaniem pobierz aktualne szczegóły, wykonaj w aplikacji kontrolę uprawnień i reguł biznesowych, zapisz wykonawcę oraz powód w dzienniku audytowym i unikaj automatycznego powtarzania. Zobacz [Returns](./reference/returns/README.md) i [Claims](./reference/claims/README.md).
