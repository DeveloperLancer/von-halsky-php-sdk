# Niezawodność i granice odpowiedzialności aplikacji

SDK wykonuje pojedyncze, typowane wywołania API. Nie uruchamia procesów roboczych, nie przechowuje punktów kontrolnych, nie zapisuje logów, nie koordynuje rozproszonych limitów i nie generuje identyfikatorów śledzenia. Te odpowiedzialności powinny być jawnie zaimplementowane w aplikacji.

## Jedna ograniczona warstwa ponawiania GET

Ponawianie jest domyślnie wyłączone. Włącz dekorator SDK tylko wtedy, gdy wstrzyknięty klient HTTP, serwer pośredniczący, siatka usług i warstwa pośrednia aplikacji nie ponawiają już żądań. Nałożone warstwy zwielokrotniają liczbę wywołań i zaburzają budżet czasu.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Reliability\RetryPolicy;

$http = SymfonyHttpClientFactory::create()->withRetry(new RetryPolicy(
    maxAttempts: 2,
    baseDelaySeconds: 0.1,
    maximumDelaySeconds: 0.5,
    maximumElapsedSeconds: 1.0,
));
```

`maxAttempts` obejmuje pierwsze żądanie, zatem wartość `2` pozwala najwyżej na jedno powtórzenie. Ponawiane mogą być wyłącznie żądania `GET` po błędzie sieciowym PSR-18 albo odpowiedzi HTTP `429`, `502`, `503` i `504`. Dla HTTP 429 respektowany jest poprawny `Retry-After`; pozostałe przypadki korzystają z wykładniczego opóźnienia z pełną losowością. Próba jest pomijana, jeżeli jej opóźnienie przekroczyłoby `maximumElapsedSeconds`.

`HttpClientDependencies::withRetry()` odrzuca dwukrotne włączenie warstwy SDK. Gdy samodzielnie budujesz zależności z zewnętrznym klientem, który już ponawia, ustaw `performsRetries: true` jako deklarację konfiguracji aplikacji i nie wywołuj `withRetry()`.

SDK nigdy automatycznie nie powtarza POST, PATCH ani DELETE. Dla zmian stanu zapisz zamiar przed wysłaniem, dodaj własny identyfikator audytowy lub klucz idempotencji tam, gdzie kontrakt serwera go obsługuje, a po niejednoznacznym błędzie transportu najpierw uzgodnij stan zewnętrzny.

## Operacje asynchroniczne poza żądaniem internetowym

HTTP 201 lub 202 może oznaczać przyjęcie asynchronicznego `CommandHandle`, a nie zakończenie operacji. Zapisz `commandId`, ID organizacji, rodzaj operacji, czas przyjęcia i własny identyfikator biznesowy. Zakończ obsługę żądania użytkownika, po czym pozwól kolejce lub harmonogramowi wykonywać ograniczone sprawdzenia `command()` albo odbierać zdarzenia.

W aplikacji utrzymuj tabelę stanów końcowych zamiast uznawać każdy nieznany stan za błąd. Ustal termin graniczny, po którym wynik asynchronicznej operacji trafia do uzgodnienia lub ręcznej analizy. SDK celowo nie usypia procesu, nie sprawdza cyklicznie, nie wybiera odstępów i nie gwarantuje czasu dostępności wyniku; nie zakładaj stałego czasu retencji bez potwierdzonej gwarancji API.

## Odtwarzanie konsumenta zdarzeń

Strumień zdarzeń jest wskazówką o zmianach, a nie jedynym źródłem prawdy. Odporny konsument:

1. Pobiera jedną stronę od najnowszego zdarzenia i przetwarza ją idempotentnie po ID.
2. Zapisuje ID zdarzenia oraz czas punktu kontrolnego dopiero po trwałym zapisie wszystkich zmian.
3. Planuje częste odczyty bez założenia, że `untilId` jest kursorem skierowanym w przyszłość.
4. Okresowo porównuje stan aplikacji z autorytatywnymi listami ofert lub zamówień.
5. Po przestoju, nieznanym zdarzeniu albo podejrzeniu luki zatrzymuje przesuwanie punktu i wykonuje pełne uzgodnienie.

Nie zakładaj stałego czasu retencji poleceń ani zdarzeń. Dopóki dostawca API nie potwierdzi go jako gwarancji, projektuj synchronizację tak, aby okresowo uzgadniała stan z listami zasobów.

## Koordynacja limitów i diagnostyka

`ApiResponse` i `RateLimitException` udostępniają odczytane metadane limitu. Ograniczaj ruch wspólnie dla wszystkich procesów używających tych samych danych uwierzytelniających; opóźnienie w jednym procesie nie zapobiegnie rozproszonemu skokowi ruchu. Po 429 lepiej zaplanować zadanie na `retryAt` niż blokować proces obsługujący użytkownika.

Zapisuj `correlationId`, `operationId`, ID organizacji, własny identyfikator żądania, numer próby i czas trwania. Nie zapisuj tokenów, nagłówków autoryzacji, pełnych modeli, treści żądań z danymi osobowymi ani całych wyjątków. Identyfikator korelacji SDK pochodzi z serwera i nie zastępuje śledzenia całego procesu w aplikacji.

## Deterministyczne zamykanie strumieni

Załączniki celowo nie są buforowane. Wywołujący odpowiada za strumienie przekazane do `upload()` i zwrócone przez `download()`. Zamykaj je w `finally`, kopiuj do miejsc z ograniczonym rozmiarem i wymuszaj limity czasu poza SDK. Pozostawiony strumień odpowiedzi może zatrzymać połączenie i ostatecznie wyczerpać pulę procesów.

Przed wydaniem skorzystaj z [listy gotowości produkcyjnej](./checklista-produkcyjna.md).
