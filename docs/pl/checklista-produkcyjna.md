# Checklista gotowości produkcyjnej

Przejdź przez tę listę przed wysłaniem ruchu produkcyjnego. Obejmuje obowiązki, które celowo pozostają po stronie aplikacji.

## Sekrety i środowiska

- Rozdziel identyfikatory klientów, sekrety, tokeny, organizacje i przestrzenie magazynu dla Stage oraz Production.
- Zapisuj cały `TokenSet` atomowo, a w środowisku wieloprocesowym lub wieloserwerowym koordynuj odświeżanie przez współdzielony `LockInterface`.
- Żądaj najmniejszego potrzebnego zestawu zakresów OAuth; nie używaj automatycznie `OAuthScope::all()` bez przeglądu potrzeb integracji.
- Nie loguj kodów autoryzacyjnych, kodu weryfikującego PKCE, sekretów klienta, tokenów, treści żądań ani pełnych modeli odpowiedzi.

## HTTP i obsługa awarii

- Ustaw timeout odpowiedni dla aplikacji i pozostaw przekierowania wyłączone.
- Włącz ponawianie SDK tylko wtedy, gdy klient HTTP, proxy, service mesh i middleware aplikacji nie robią tego wcześniej.
- Obsługuj osobno typowane wyjątki API, błędy transportu, mapowania odpowiedzi i przepływu OAuth.
- Traktuj HTTP 429 jako sygnał do koordynacji: użyj `RateLimit`, ogranicz ruch między instancjami aplikacji i zachowaj identyfikator korelacji dla wsparcia technicznego.

## Zmiana stanu i operacje asynchroniczne

- Nie uznawaj HTTP 201 ani 202 za zakończenie operacji biznesowej; zapisuj ID polecenia i czas jego przyjęcia.
- Sprawdzanie komend i pobieranie zdarzeń uruchamiaj w ograniczonych zadaniach tła, a nie w długo działającym żądaniu WWW.
- Uzgadnianie danych wykonuj idempotentnie, a stronę lub punkt kontrolny zapisuj transakcyjnie razem ze zmienianym stanem.
- Dla każdego POST, PATCH i DELETE zapewnij autoryzację, audyt oraz ochronę przed duplikatem; SDK nigdy nie ponawia tych metod.

## Dane i strumienie

- Stosuj minimalizację danych i retencję do zamówień, zwrotów i reklamacji; zagnieżdżone tablice mogą zawierać dane osobowe.
- Zamykaj przekazane strumienie uploadu oraz pobrane strumienie odpowiedzi w blokach `finally`.
- Oprócz ID zdarzenia zapisuj czas punktu kontrolnego i przygotuj uzgadnianie z pełną listą po luce retencji.
- Monitoruj typ wyjątku, operation ID, status HTTP, limity i correlation ID bez zapisywania wrażliwych payloadów.

## Weryfikacja wydania

Uruchom pełną bramkę offline i wygeneruj referencję z dokładnie tej rewizji, która ma zostać wdrożona:

```bash
composer quality
composer phpstan:max
composer docs-build
```

Testy Stage uruchamiaj wyłącznie na dedykowanych danych, zgodnie z [procedurą weryfikacji Stage](https://github.com/DeveloperLancer/von-halsky-php-sdk/blob/main/tools/contract/STAGE.md).
