# Odpowiedzi, paginacja, walidacja i błędy

Każda udana metoda zasobu zwraca `ApiResponse<T>`. Wynik domenowy znajduje się w `data`, a metadane transportu są dostępne obok niego zamiast w zmiennym stanie klienta.

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\Resource\OrganizationsResource $organizacje */
$odpowiedz = $organizacje->list();

$elementy = $odpowiedz->data;
$status = $odpowiedz->statusCode;
$identyfikatorKorelacji = $odpowiedz->correlationId;
```

Pola odpowiedzi mają osobne zastosowania:

| Pole | Znaczenie |
| --- | --- |
| `data` | Model właściwy dla operacji, opisany jako `T`. |
| `statusCode` | Kod HTTP udanej odpowiedzi. |
| `headers` | Niezmienna, bezpieczna lista wybranych nagłówków, a nie wszystkie nagłówki serwera. |
| `rateLimit` | Informacje o limicie, jeśli wystąpił co najmniej jeden odpowiedni nagłówek. |
| `correlationId` | Wartość `X-Correlation-ID`, a w razie jej braku `X-Request-ID`. |

`RateLimit` może zawierać `limit`, `remaining`, czas UTC `resetAt`, czas UTC `retryAt` i liczbę sekund `retryAfterSeconds`. Każde pole może być `null`; SDK nie zgaduje brakujących lub błędnych nagłówków. Traktuj te dane jako sygnał do wspólnego ograniczania ruchu w aplikacji, a nie gwarancję powodzenia następnego żądania.

## Obiekty wartości i walidacja lokalna

Publiczne identyfikatory są osobnymi, niezmiennymi typami, na przykład `OrganizationId`, `OfferId`, `OrderId` i `CommandId`. Zachowanie typu zwróconego przez jedną metodę utrudnia omyłkowe przekazanie ID zamówienia zamiast ID oferty. Kwoty twórz z zapisu dziesiętnego, nigdy z binarnego `float`:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\Money;

$cena = Money::fromDecimal('49.90'); // Wynik: 49.90 PLN.
```

`Money` przyjmuje wartości od `0.01` do `999999.99`, z najwyżej dwiema cyframi po separatorze, i domyślnie używa PLN. Obiekty żądań sprawdzają potwierdzone ograniczenia przed komunikacją z siecią i zgłaszają `InvalidRequestException` ze ścieżką pola. Walidacja lokalna daje szybszą informację, ale nie zastępuje aktualnych reguł biznesowych serwera.

W żądaniu JSON Merge Patch `OptionalValue::undefined()`, `OptionalValue::null()` i `OptionalValue::of($wartosc)` oznaczają odpowiednio: pominięcie pola, wysłanie JSON `null` oraz wysłanie konkretnej wartości. To rozróżnienie chroni przed przypadkowym wyczyszczeniem pominiętego pola.

Wyliczenia odpowiedzi oparte na `ExtensibleEnum` zachowują nieznane wartości serwera, co ułatwia zgodność z przyszłymi zmianami. Porównuj ich pole `value` i obsługuj wariant nieznany w logice biznesowej. Tekstowe filtry są sprawdzane lokalnie tylko wtedy, gdy SDK ma potwierdzony zbiór wartości; serwer nadal może odrzucić wartość zmienioną po jego stronie.

## Paginacja

Metody listujące zwracają `ApiResponse<PageResult<T>>`. SDK celowo pobiera jedną stronę. `items` zawiera typowane rekordy, `page` zawiera `offset`, `limit` i `total`, a zarówno `PageResult`, jak i `Page` mogą zachować nieznane metadane w `additionalData`.

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\Pagination\PageResult $strona */
foreach ($strona->items as $element) {
    // Zapisz element w sposób idempotentny.
}

$przetworzonoDo = $strona->page->offset + count($strona->items);
$jestNastepna = $strona->items !== [] && $przetworzonoDo < $strona->page->total;
```

Nie opieraj pętli wyłącznie na warunku `count($items) === $requestedLimit`: limit zwrócony przez serwer może być inny, pusta strona musi zakończyć pętlę, a równoległe zmiany mogą przesuwać zbiór oparty na przesunięciu. Zapisz stronę przed przesunięciem punktu kontrolnego, usuwaj duplikaty po stabilnym ID i okresowo uzgadniaj dane z autorytatywną listą. Strumienie zdarzeń są ograniczonymi czasowo listami od najnowszego elementu, a nie pełną historią ani kursorem skierowanym w przyszłość.

## Model wyjątków

Warstwy błędów są rozdzielone, aby aplikacja mogła wybrać właściwą reakcję:

| Warstwa | Wyjątek | Typowa reakcja |
| --- | --- | --- |
| Budowanie żądania | `InvalidRequestException` | Popraw dane; nie ponawiaj ich bez zmiany. |
| HTTP 400/401/403/404/409/422/429/5xx z API | Typowana klasa potomna `ApiException` | Sprawdź status, operację, szczegóły i identyfikator korelacji. |
| Inna odpowiedź API spoza 2xx | `ApiException` | Reaguj zgodnie ze statusem i semantyką operacji. |
| Błąd sieciowy PSR-18 | `NetworkTransportException` | Ponów tylko bezpieczną operację i wyłącznie w granicach polityki. |
| Błędne żądanie PSR-18 | `InvalidTransportRequestException` | Popraw konfigurację transportu lub budowanie żądania. |
| Inny błąd klienta transportowego | `TransportException` | Zdiagnozuj wstrzyknięty transport. |
| Niezgodny model udanej odpowiedzi | `ResponseMappingException` | Zachowaj metadane korelacji i sprawdź niezgodność kontraktu. |
| Endpoint OAuth lub stan tokenu | `AuthenticationFlowException` | Ponów autoryzację albo napraw magazyn tokenów; komunikaty są zredagowane. |

Typowane wyjątki HTTP to `BadRequestException`, `AuthenticationException`, `AuthorizationException`, `NotFoundException`, `ConflictException`, `UnprocessableEntityException`, `RateLimitException` oraz `ServerException`.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\RateLimitException;

try {
    $odpowiedz = $sklep->offers()->get($idOferty);
} catch (InvalidRequestException $blad) {
    // Odrzuć lub popraw dane lokalne.
} catch (RateLimitException $blad) {
    // Zaplanuj późniejszy odczyt na podstawie $blad->rateLimit; unikaj ciasnej pętli.
} catch (ApiException $blad) {
    // Loguj wyłącznie zatwierdzone pola, np. statusCode, operationId i correlationId.
}
```

`ApiException` udostępnia `statusCode`, `errorCode`, strukturalne `details`, bezpieczne nagłówki, opcjonalne dane limitu, `correlationId` oraz identyfikator operacji SDK `operationId`. Jeśli treść błędu jest nieprawidłowa, SDK może zachować zredagowany fragment ograniczony do 256 bajtów. Nawet ten fragment traktuj jako potencjalnie wrażliwy i domyślnie go nie loguj. Nagłówek autoryzacji ani pełna treść odpowiedzi nie są przechowywane.
