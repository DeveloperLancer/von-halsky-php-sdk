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

### Utworzenie i walidacja atrybutu

Nie twórz ręcznie `AttributeDefinition`. Pobierz definicje dla wybranej kategorii i użyj ID konkretnej definicji jako ID `AttributeValue`. Poniższy przykład tworzy jedną wartość atrybutu, umieszcza ją w produkcie i uruchamia zalecaną walidację całego `ProductProposal`:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Validation\CategoryProductValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$categoryId = new CategoryId('leaf-category-id');
$definitions = $client->categories()->attributes(
    $categoryId,
    ResponseLanguage::POLISH,
)->data;

$attributeId = 'attribute-id-returned-by-api';
$definition = null;
foreach ($definitions as $candidate) {
    if ($candidate->id === $attributeId) {
        $definition = $candidate;
        break;
    }
}

if ($definition === null) {
    throw new LogicException('Atrybut nie należy do wybranej kategorii.');
}

$attribute = new AttributeValue(
    id: $definition->id,
    values: ['123'],
    language: $definition->language,
);

$proposal = new ProductProposal(
    name: 'Example product',
    description: 'This example product description is longer than one hundred characters, so it meets the local offer-form requirement.',
    brand: 'Example',
    categoryId: $categoryId,
    ean: new Ean('5901234123457'),
    attributes: [$attribute],
);

$validator = new CategoryProductValidator($categoryId, $definitions);
$validation = $validator->validate($proposal);

foreach ($validation->issues as $issue) {
    // Użyj $issue->level, $issue->fieldPath i $issue->message.
}
```

`values` jest zawsze listą stringów, również dla pojedynczej wartości. Oficjalny wspólny schemat `AttributeValueItem` dopuszcza pusty string, ogranicza każdy element do 1024 znaków i nie ustawia `minItems`, dlatego samo `[]` jest poprawne strukturalnie. SDK zapisuje obecny limit 1024 osobno w każdym wbudowanym walidatorze typu, aby przyszła zmiana jednego typu nie zmieniała automatycznie pozostałych. Dopuszczalną liczbę elementów określa definicja:

| `expectedValue` | Dopuszczalna liczba elementów `values` |
| --- | --- |
| `NULL_OR_ONE` | 0 lub 1 |
| `ONE` | dokładnie 1 |
| `AT_LEAST_ONE` | co najmniej 1 |
| `ANY` | 0, 1 lub wiele |

Wartości muszą też odpowiadać typowi definicji. Przykładowe `'123'` jest poprawnym kandydatem dla `NUMERIC`, ale nie musi być poprawne dla definicji wybranej w rzeczywistej kategorii. `DictionaryValueValidator` porównuje wartość dokładnie z `option->value` w `$definition->dictionary`: aktywna opcja jest akceptowana, nieaktywna zwraca `dictionary_value_inactive`, a nieznana wartość lub ID opcji zwraca `dictionary_value_unknown`. Jeśli definicja nie zawiera słownika, `CategoryProductValidator` zwraca ostrzeżenie `dictionary_missing`. Pusta lista w `UpsertAttribute` jest serializowana zgodnie z kontraktem, ale do jednoznacznego usunięcia atrybutu służy `RemoveAttribute`.

Jeśli potrzebujesz sprawdzić wyłącznie format jednej wartości, możesz jawnie utworzyć kontekst i wywołać rejestr typów. Indeksy muszą wskazywać rzeczywistą pozycję atrybutu i wartości w produkcie; w powyższym przykładzie oba wynoszą `0`:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;

/** @var \DevLancer\VonHalsky\ValueObject\CategoryId $categoryId */
/** @var \DevLancer\VonHalsky\Model\Category\AttributeDefinition $definition */
/** @var \DevLancer\VonHalsky\Model\Offer\AttributeValue $attribute */
$context = new AttributeValueValidationContext(
    categoryId: $categoryId,
    definition: $definition,
    attribute: $attribute,
    attributeIndex: 0,
    valueIndex: 0,
);

$registry = AttributeValueTypeValidatorRegistry::withDefaults();
$typeValidation = $registry->validate($context);

foreach ($typeValidation->errors() as $error) {
    // Ścieżka znajduje się w $context->fieldPath, a opis w $error->message.
}
foreach ($typeValidation->warnings() as $warning) {
    // Ostrzeżenia nie powodują $typeValidation->isValid() === false.
}
```

Bezpośrednie wywołanie rejestru uruchamia wszystkie reguły wybranego walidatora typu, w tym jego własny limit długości oraz — dla `DICTIONARY` — przynależność do aktywnych opcji słownika. Nie sprawdza kompletności produktu, krotności atrybutu ani wymaganych atrybutów. Do walidacji danych przed utworzeniem lub aktualizacją oferty używaj `CategoryProductValidator::validate()`.

Walidator sprawdza zgodność kategorii, wymagane atrybuty, krotność, powtórzone lub nieznane identyfikatory, aktywne wartości słownikowe oraz znane typy wartości. Każdy wbudowany typ ma obecnie własny limit 1024 znaków. `NUMERIC` przyjmuje nieujemne liczby całkowite bez znaku, `NUMERIC_FLOAT` nieujemne liczby dziesiętne z kropką, `DATE` datę ISO `YYYY-MM-DD`, a `URL` bezwzględny adres HTTP lub HTTPS. Dla słownika przekazuj zlokalizowane `value` opcji zwrócone przez API, a nie ID opcji. Nieznane przyszłe typy definicji powodują ostrzeżenia, natomiast brak zarejestrowanego walidatora dla typu znanego API jest błędem. Walidacja lokalna nie zastępuje aktualnych reguł biznesowych serwera i nigdy nie jest uruchamiana automatycznie przez tworzenie oferty.

Aplikacja może zarejestrować własny typ. Walidator otrzymuje kategorię, definicję, cały atrybut, bieżącą wartość, indeksy i ścieżkę pola. Zwraca listę błędów i ostrzeżeń, które `CategoryProductValidator` dołącza do wyniku produktu. Własny typ sam określa swój limit. Trait `ValidatesAttributeValueLength` udostępnia wspólną mechanikę bez narzucania wspólnej wartości limitu:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationIssue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeType\ValidatesAttributeValueLength;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;
use DevLancer\VonHalsky\Validation\CategoryProductValidator;

final class ApplicationCodeValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 64;

    public function type(): string
    {
        return 'APPLICATION_CODE';
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, $this->type());
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }
        if (preg_match('/\AAPP-\d+\z/D', $context->value) !== 1) {
            $issues[] = new AttributeValueTypeValidationIssue(
                'application_code_invalid',
                AttributeValueTypeValidationIssue::ERROR,
                'Kod aplikacyjny musi mieć format APP-123.',
            );
        }

        return new AttributeValueTypeValidationResult($issues);
    }
}

$registry = AttributeValueTypeValidatorRegistry::withDefaults([
    new ApplicationCodeValidator(),
]);
$validator = new CategoryProductValidator($proposal->categoryId, $definitions, $registry);
```

Bezpośrednie wywołanie rejestru dla niezarejestrowanego typu rzuca `LogicException`. Aby nadpisać regułę wbudowaną, usuń ją i dodaj własną przez `remove()->add()`.

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

## Formatowanie opisu produktu

Opis produktu można formatować HTML. SDK wysyła wartość `ProductProposal::$description` bez konwersji lub filtrowania znaczników; ogranicza wyłącznie długość całego tekstu do `100–100000` znaków.

| Efekt | HTML |
| --- | --- |
| Pogrubienie | `<strong>tekst</strong>` |
| Pochylenie | `<em>tekst</em>` |
| Podkreślenie | `<u>tekst</u>` |
| Lista punktowana | `<ul><li>element</li></ul>` |
| Lista numerowana | `<ol><li>element</li></ol>` |

Walidacja i ewentualne oczyszczanie HTML pozostają po stronie API, dlatego nie zakładaj, że dowolne znaczniki będą obsługiwane.

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
