# Polska mapa operacji

Ta strona opisuje po polsku wszystkie 40 aktualnych metod zasobów SDK. Link w ostatniej kolumnie prowadzi do pełnej polskiej strony referencyjnej z przykładem PHP, parametrami i ograniczeniami.

Wszystkie udane wywołania zwracają `ApiResponse<T>`. Zasoby globalne są dostępne z `$client`; zasoby organizacji z `$shop = $client->forOrganization($organizationId)`.

## Organizacje i kategorie

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `organizations()->list()` | globalny, `list<Organization>` | Organizacje dostępne dla obecnego tokenu. | [PL](./reference/organizations/list.md) |
| `categories()->list()` | globalny, `list<Category>` | Ograniczone drzewo kategorii; głębokość `0–10`. | [PL](./reference/categories/list.md) |
| `categories()->get()` | globalny, `Category` | Jedna kategoria i ograniczone potomstwo. | [PL](./reference/categories/get.md) |
| `categories()->attributes()` | globalny, `list<AttributeDefinition>` | Definicje atrybutów wymaganych/opcjonalnych dla kategorii. | [PL](./reference/categories/attributes.md) |

`ProductProposal` wymaga `leaf category`. Wywołaj `requireLeaf()` lub przekaż `CategoryId` wcześniej potwierdzony przez katalog jako kategorię bez podkategorii.

## Oferty

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `offers()->depositTypes()` | globalny, `list<DepositType>` | Słownik typów depozytów. | [PL](./reference/offers/deposit-types.md) |
| `$shop->offers()->list()` | organizacja, `PageResult<OfferDetails>` | Jedna strona ofert z filtrami i sortowaniem. | [PL](./reference/offers/list.md) |
| `$shop->offers()->get()` | organizacja, `OfferDetails` | Szczegóły jednej oferty. | [PL](./reference/offers/get.md) |
| `$shop->offers()->create()` | organizacja, `CommandHandle` | Asynchroniczne utworzenie jednej oferty. | [PL](./reference/offers/create.md) |
| `$shop->offers()->createBatch()` | organizacja, `list<CommandHandle>` | Od 1 do 500 poleceń tworzenia oferty. | [PL](./reference/offers/create-batch.md) |
| `$shop->offers()->patch()` | organizacja, `OfferDetails` | Merge patch z `OptionalValue`. | [PL](./reference/offers/patch.md) |
| `$shop->offers()->updatePrices()` | organizacja, `list<CommandHandle>` | Niepusta lista aktualizacji cen. | [PL](./reference/offers/update-prices.md) |
| `$shop->offers()->updateStocks()` | organizacja, `list<CommandHandle>` | Niepusta lista aktualizacji stanów. | [PL](./reference/offers/update-stocks.md) |
| `$shop->offers()->updateAttributes()` | organizacja, `CommandHandle` | Uporządkowane operacje dodania, aktualizacji i usunięcia atrybutów. | [PL](./reference/offers/update-attributes.md) |
| `$shop->offers()->close()` | organizacja, `CommandHandle` | Asynchroniczne zamknięcie oferty. | [PL](./reference/offers/close.md) |
| `$shop->offers()->reopen()` | organizacja, `CommandHandle` | Asynchroniczne ponowne otwarcie oferty. | [PL](./reference/offers/reopen.md) |
| `$shop->offers()->command()` | organizacja, `CommandDetails` | Jednorazowy odczyt stanu `command` oferty. | [PL](./reference/offers/command.md) |
| `$shop->offers()->events()` | organizacja, `list<OfferEvent>` | Jedna strona zdarzeń, od najnowszych. | [PL](./reference/offers/events.md) |
| `$shop->offers()->hints()` | organizacja, `PageResult<ProductHint>` | Sugestie na podstawie EAN, MPN lub nazwy. | [PL](./reference/offers/hints.md) |

`create()`, aktualizacje i operacje cyklu życia zwracają `CommandHandle`, a nie końcowy stan. Zapisz `commandId` i odczytaj później `command()` lub zdarzenia. Metody zapisujące nie są automatycznie ponawiane.

## Załączniki

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `$shop->attachments()->list()` | organizacja, `PageResult<AttachmentInfo>` | Jedna strona metadanych załączników oferty. | [PL](./reference/attachments/list.md) |
| `$shop->attachments()->upload()` | organizacja, `CommandHandle` | Wysłanie należącego do aplikacji strumienia PSR-7. | [PL](./reference/attachments/upload.md) |
| `$shop->attachments()->download()` | organizacja, `DownloadedAttachment` | Strumień odpowiedzi zamykany przez aplikację. | [PL](./reference/attachments/download.md) |
| `$shop->attachments()->delete()` | organizacja, `null` | Usunięcie zdalnego załącznika. | [PL](./reference/attachments/delete.md) |

SDK nie buforuje całego pliku ani nie zamyka strumieni za aplikację.

## Zamówienia

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `orders()->deliveryMethods()` | globalny, `list<DeliveryMethod>` | Aktualny słownik metod dostawy v2. | [PL](./reference/orders/delivery-methods.md) |
| `$shop->orders()->list()` | organizacja, `PageResult<OrderDetails>` | Jedna strona zamówień, opcjonalnie od znacznika czasu UTC. | [PL](./reference/orders/list.md) |
| `$shop->orders()->get()` | organizacja, `OrderDetails` | Jedno zamówienie; obsługuj je jak dane potencjalnie osobowe. | [PL](./reference/orders/get.md) |
| `$shop->orders()->accept()` | organizacja, `OrderCommand` | Przyjęcie zamówienia przez `command`. | [PL](./reference/orders/accept.md) |
| `$shop->orders()->command()` | organizacja, `OrderCommand` | Jednorazowy odczyt `command` zamówienia. | [PL](./reference/orders/command.md) |
| `$shop->orders()->events()` | organizacja, `list<OrderEvent>` | Jedna strona zdarzeń od najnowszych. | [PL](./reference/orders/events.md) |
| `$shop->orders()->refund()` | organizacja, `RefundResult` | Pełny zwrot płatności bez DTO lub dokładny częściowy z `RefundRequest`. | [PL](./reference/orders/refund.md) |

## Returns (zwroty towarów)

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `$shop->returns()->list()` | organizacja, `PageResult<ReturnDetails>` | Jedna strona zwrotów organizacji. | [PL](./reference/returns/list.md) |
| `$shop->returns()->forOrder()` | organizacja, `PageResult<ReturnDetails>` | Zwroty przypisane do zamówienia. | [PL](./reference/returns/for-order.md) |
| `$shop->returns()->get()` | organizacja, `ReturnDetails` | Szczegóły jednego zwrotu. | [PL](./reference/returns/get.md) |
| `$shop->returns()->accept()` | organizacja, `ActionResult` | Akceptacja zwrotu. | [PL](./reference/returns/accept.md) |
| `$shop->returns()->reject()` | organizacja, `ActionResult` | Odrzucenie zwrotu. | [PL](./reference/returns/reject.md) |

Akceptacja i odrzucenie zmieniają stan zewnętrzny, dlatego nie są automatycznie ponawiane.

## Claims (reklamacje)

| Metoda | Zakres i wynik | Opis | Szczegóły |
|---|---|---|---|
| `claims()->types()` | globalny, `list<ClaimType>` | Lokalizowany słownik typów reklamacji. | [PL](./reference/claims/types.md) |
| `$shop->claims()->list()` | organizacja, `PageResult<ClaimDetails>` | Jedna strona reklamacji z filtrami. | [PL](./reference/claims/list.md) |
| `$shop->claims()->get()` | organizacja, `ClaimDetails` | Reklamacja w zakresie konkretnego zamówienia. | [PL](./reference/claims/get.md) |
| `$shop->claims()->reject()` | organizacja, `ActionResult` | Odrzucenie reklamacji z opcjonalnym opisem. | [PL](./reference/claims/reject.md) |
| `$shop->claims()->partialRefund()` | organizacja, `ActionResult` | Częściowy zwrot płatności w reklamacji. | [PL](./reference/claims/partial-refund.md) |
| `$shop->claims()->refund()` | organizacja, `ActionResult` | Zwrot płatności w reklamacji. | [PL](./reference/claims/refund.md) |

Akcje reklamacyjne mogą mieć konsekwencje finansowe i dla klienta. Przed wywołaniem zweryfikuj stan reklamacji, uprawnienia aplikacji i uzasadnienie biznesowe.
