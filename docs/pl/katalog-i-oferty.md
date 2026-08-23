# Katalog, oferty i załączniki

Katalog jest globalny, natomiast oferty i załączniki należą do organizacji. Bezpieczny przebieg wygląda następująco: wybierz organizację, znajdź `leaf category` (kategorię bez podkategorii), pobierz atrybuty, zweryfikuj propozycję produktu, wyślij żądanie utworzenia oferty, a wynik obserwuj poza pierwotnym żądaniem aplikacji internetowej.

## Odczyt katalogu

`categories()->list()` zwraca ograniczone drzewo, natomiast `get()` i `attributes()` dostarczają szczegółów potrzebnych do budowy produktu. Elementy potomne obecne w `Category` są wyłącznie danymi zawartymi w tej odpowiedzi; ich odczyt nie wykonuje ukrytych żądań HTTP.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\CategoryTreeOptions;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$categories = $client->categories()->list(
    new CategoryTreeOptions(depth: 4),
)->data;
```

Przekazanie pobranego obiektu `Category` do `ProductProposal` uruchamia `Category::requireLeaf()` i lokalnie odrzuca znany obiekt, który nie jest `leaf category`. Sam `CategoryId` nie pozwala tego stwierdzić, dlatego SDK ufa wywołującemu, a serwer pozostaje źródłem rozstrzygającym. Przy tworzeniu produktu preferuj świeżo pobraną kategorię. Nieznane wartości wyliczeń odpowiedzi są zachowywane; opisuje to przewodnik [odpowiedzi i błędy](./odpowiedzi-i-bledy.md).

## Budowa poprawnej oferty

Najważniejsze ograniczenia sprawdzane lokalnie:

| Wartość | Potwierdzone ograniczenie SDK |
| --- | --- |
| Nazwa produktu | 1–150 znaków |
| Opis | 1–100000 znaków |
| Marka | 1–100 znaków |
| Identyfikatory produktu | Co najmniej EAN albo numer katalogowy producenta |
| Atrybuty produktu | Najwyżej 20 pozycji |
| Stan magazynowy | 0–999999 |
| Kwota brutto | `0.01`–`999999.99`, najwyżej dwa miejsca dziesiętne |
| Opis stawki podatku | 1–100 znaków |
| Dni do wysyłki | 0–60, jeśli podano |
| Tworzenie grupowe | 1–500 ofert |
| Instrukcje GPSR | Najwyżej 20; tytuł 5–500, URL 9–2048 znaków |

`GpsrInfo::required()` sprawdza również niepustą nazwę producenta, poprawny adres e-mail producenta i niepustą informację o bezpieczeństwie. `GpsrInfo::notRequired()` zapisuje jawne wyłączenie przewidziane przez kontrakt; nie używaj go wyłącznie w celu obejścia brakujących danych zgodności.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;

/** @var \DevLancer\VonHalsky\Model\Category\Category $leafCategory */
/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$response = $shop->offers()->create(new CreateOfferRequest(
    product: new ProductProposal(
        name: 'Example product',
        description: 'A safe example description.',
        brand: 'Example',
        categoryId: $leafCategory,
        ean: new Ean('5901234123457'),
    ),
    stock: new Stock(10),
    price: new Price(Money::fromDecimal('49.90'), '23%'),
    gpsr: GpsrInfo::notRequired(),
    daysToShip: 2,
));

$commandId = $response->data->commandId;
```

HTTP 201 potwierdza przyjęcie `CommandHandle`, a nie dostępność oferty. Zapisz `commandId` i czas przyjęcia w tym samym trwałym rekordzie procesu, po czym pozwól zadaniu okresowemu wywołać `command()` albo odebrać `events()`. Nie utrzymuj otwartego żądania internetowego podczas sprawdzania wyniku.

## Świadome aktualizowanie ofert

Do grupowych zmian cen i stanów używaj odpowiednich obiektów danych, do semantyki merge patch — `PatchOfferRequest` z `OptionalValue`, a do atrybutów — uporządkowanych operacji `UpsertAttribute` i `RemoveAttribute`. Udane synchroniczne `patch()` oraz przyjęty `CommandHandle` mają inne znaczenie; typ wyniku podają strony [referencji ofert](./reference/offers/README.md).

Tworzenie, aktualizacja, zamknięcie, ponowne otwarcie, wysyłanie i usuwanie zmieniają stan zewnętrzny. SDK nie ponawia ich automatycznie. W Stage uruchamiaj je wyłącznie po jawnym włączeniu operacji zapisu, na danych syntetycznych, i zapisuj własny `commandId` lub identyfikator audytu. Po niejednoznacznym błędzie transportu najpierw uzgodnij stan, a dopiero potem rozważ kolejne żądanie zapisu.

## Odpowiedzialność za strumienie załączników

`upload()` czyta należący do wywołującego strumień PSR-7 bez buforowania i nie zamyka go. `download()` zwraca strumień odpowiedzi bez wczytywania całego załącznika do pamięci. Aplikacja musi zamknąć oba rodzaje strumieni, także po wyjątku:

```php
<?php

declare(strict_types=1);

/** @var \Psr\Http\Message\StreamInterface $stream */
try {
    $download = $shop->attachments()->download($offerId, $attachmentId)->data;
    $stream = $download->stream;
    // Kopiuj porcjami do miejsca należącego do aplikacji.
} finally {
    if (isset($stream)) {
        $stream->close();
    }
}
```

Aplikacja odpowiada za sprawdzenie nazwy pliku, typu MIME, ścieżki docelowej, limitu rozmiaru i zasad ochrony przed złośliwymi plikami. Zobacz [referencję załączników](./reference/attachments/README.md) i [niezawodność produkcyjną](./niezawodnosc.md).
