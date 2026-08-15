# Dokumentacja SDK Von Halsky po polsku

To jest polska, śledzona w Git wersja dokumentacji SDK. Opisuje te same publiczne zachowania co [wersja angielska](../en/README.md); obie wersje należy aktualizować razem ze zmianą publicznego API.

Przewodniki są przeznaczone dla programistów PHP znających Composer i podstawy HTTP API. Zacznij od przewodnika opisującego cały proces integracji, a do referencji przejdź, gdy znasz już potrzebną metodę zasobu.

## Ścieżki czytania

### Pierwsza integracja

1. [Instalacja i pierwszy klient](./instalacja.md)
2. [Klient, środowiska i kontekst organizacji](./klient-i-srodowiska.md)
3. [Odpowiedzi, paginacja, walidacja i błędy](./odpowiedzi-i-bledy.md)

### OAuth i tokeny

1. [OAuth 2.0 i cykl życia tokenu](./uwierzytelnianie.md)
2. [Klient, środowiska i kontekst organizacji](./klient-i-srodowiska.md)

### Katalog i oferty

1. [Katalog, oferty i załączniki](./katalog-i-oferty.md)
2. [Polska pełna referencja operacji](./reference/README.md)

### Zamówienia i obsługa posprzedażowa

1. [Zamówienia i obsługa posprzedażowa](./zamowienia-i-posprzedaz.md)
2. [Polska pełna referencja operacji](./reference/README.md)

### Niezawodność produkcyjna

1. [Niezawodność i granice odpowiedzialności aplikacji](./niezawodnosc.md)
2. [Odpowiedzi, paginacja, walidacja i błędy](./odpowiedzi-i-bledy.md)
3. [Checklista gotowości produkcyjnej](./checklista-produkcyjna.md)

## Przewodniki

- [Instalacja i pierwszy klient](./instalacja.md)
- [Klient, środowiska i kontekst organizacji](./klient-i-srodowiska.md)
- [OAuth 2.0 i cykl życia tokenu](./uwierzytelnianie.md)
- [Katalog, oferty i załączniki](./katalog-i-oferty.md)
- [Zamówienia i obsługa posprzedażowa](./zamowienia-i-posprzedaz.md)
- [Odpowiedzi, paginacja, walidacja i błędy](./odpowiedzi-i-bledy.md)
- [Niezawodność i granice odpowiedzialności aplikacji](./niezawodnosc.md)
- [Checklista gotowości produkcyjnej](./checklista-produkcyjna.md)
- [Kompatybilność](./kompatybilnosc.md)

## Pełna referencja

[Polska referencja](./reference/README.md) ma osobną stronę z opisem, sygnaturą, wynikiem i przykładem dla każdej z 42 metod. [Mapa operacji](./referencja-operacji.md) pozostaje skróconą ściągą.

- [Przykłady uruchamialne](../../examples/README.md)
- [Generowana referencja klas PHP](./referencja-php.md)

## Utrzymanie dokumentacji

Zmiana publicznego API wymaga równoczesnej aktualizacji obu wersji językowych i odpowiednich stron operacji. `composer docs-check` sprawdza kompletność referencji, linki lokalne i składnię przykładów PHP.
