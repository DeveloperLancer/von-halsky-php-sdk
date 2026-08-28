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

## Jawna walidacja wymagań kategorii

Walidacja zależna od kategorii jest opcjonalna. `productValidator()` wykonuje jedno żądanie definicji atrybutów i zwraca walidator skonfigurowany dla tej kategorii. Wywołanie `validate()` nie wykonuje żądań HTTP i zwraca wszystkie wykryte błędy oraz ostrzeżenia zamiast rzucać wyjątek dla problemów z danymi produktu:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ResponseLanguage;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
/** @var \DevLancer\VonHalsky\Model\Offer\ProductProposal $proposal */
$validatorResponse = $client->categories()->productValidator(
    $proposal->categoryId,
    ResponseLanguage::POLISH,
);
$validation = $validatorResponse->data->validate($proposal);

if (!$validation->isValid()) {
    foreach ($validation->errors() as $error) {
        // Pokaż $error->fieldPath i $error->message przed wysłaniem oferty.
    }
}
```

Jeśli aplikacja ma już aktualne albo zapisane w pamięci podręcznej definicje, może utworzyć ten sam walidator bez kolejnego żądania:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\CategoryProductValidator;

/** @var list<\DevLancer\VonHalsky\Model\Category\AttributeDefinition> $definitions */
/** @var \DevLancer\VonHalsky\Model\Offer\ProductProposal $proposal */
$validator = new CategoryProductValidator($proposal->categoryId, $definitions);
$validation = $validator->validate($proposal);
```

Walidator sprawdza zgodność kategorii, wymagane atrybuty, krotność, powtórzone lub nieznane identyfikatory oraz aktywne wartości słownikowe. Nieznane przyszłe typy definicji powodują ostrzeżenia. Narzędzie celowo nie zgaduje formatów liczbowych, dat ani adresów URL, których metadane kategorii nie określają. Walidacja lokalna nie zastępuje aktualnych reguł biznesowych serwera i nigdy nie jest uruchamiana automatycznie przez tworzenie oferty.

## Budowa poprawnej oferty

Najważniejsze reguły formularza oferty sprawdzane lokalnie:

| Wartość | Reguła SDK                                                                                                  |
| --- |-------------------------------------------------------------------------------------------------------------|
| Nazwa produktu | 7–150 znaków                                                                                                |
| Opis | 100–100000 znaków                                                                                           |
| Marka | 1–100 znaków                                                                                                |
| Model i supermodel | Po 1–100 znaków, jeśli podano                                                                               |
| SKU | 1–100 znaków                                                                                                |
| Grafiki oferty | 1–20 pozycji; nazwa pliku z rozszerzeniem `.jpg`, `.png` albo `.webp`                                       |
| Identyfikatory produktu | Co najmniej EAN albo numer katalogowy producenta                                                            |
| Atrybuty produktu | Najwyżej 120 pozycji                                                                                        |
| Stan magazynowy | 0–999999                                                                                                    |
| Kwota brutto | `0.01`–`999999.99`, najwyżej dwa miejsca dziesiętne                                                         |
| Opis stawki podatku | 1–100 znaków                                                                                                |
| Dni do wysyłki | 0–60, jeśli podano                                                                                          |
| Tworzenie grupowe | 1–500 ofert                                                                                                 |
| GPSR producenta | Nazwa, e-mail i osoba odpowiedzialna: maks. 500; adres niestrukturalny: maks. 300; telefon: `+` i 3–15 cyfr |
| Informacje GPSR | Informacja o bezpieczeństwie: maks. 100000; numer partii: maks. 500; oznaczenie CE: wartość logiczna        |
| Instrukcje GPSR | Najwyżej 20; tytuł 5–500, URL 9–2048 znaków                                                                 |

`GpsrInfo::required()` wymaga nazwy producenta, jego pełnego adresu oraz poprawnego adresu e-mail, a także niepustej informacji o bezpieczeństwie. `GpsrInfo::notRequired()` zapisuje jawne wyłączenie przewidziane przez kontrakt; nie używaj go wyłącznie w celu obejścia brakujących danych zgodności.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CountryCode;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\Sku;

/** @var \DevLancer\VonHalsky\Model\Category\Category $leafCategory */
/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$response = $shop->offers()->create(new CreateOfferRequest(
    product: new ProductProposal(
        name: 'Example product',
        description: 'This example product description is longer than one hundred characters, so it meets the local offer-form requirement.',
        brand: 'Example',
        categoryId: $leafCategory,
        ean: new Ean('5901234123457'),
        sku: new Sku('EXAMPLE-001'),
    ),
    stock: new Stock(10),
    price: new Price(Money::fromDecimal('49.90'), '23%'),
    gpsr: GpsrInfo::required(
        'Example manufacturer',
        new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10'),
        'manufacturer@example.com',
        'Keep this product away from children.',
    ),
    daysToShip: 2,
    images: [new OfferImage('example-product.webp', 'https://example.com/example-product.webp', 1)],
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
