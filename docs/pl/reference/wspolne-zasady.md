# Wspólne zasady referencji

Każda udana operacja zwraca `ApiResponse<T>`. Model domenowy jest w `data`, a `statusCode`, bezpieczne nagłówki, opcjonalny `rateLimit` i `correlationId` są dostępne obok niego.

Odpowiedzi inne niż 2xx powodują odpowiedni `ApiException`; błędy sieciowe i PSR-18 są mapowane na wyjątki transportowe, a poprawna odpowiedź HTTP z niezgodnym JSON-em lub strukturą — na `ResponseMappingException`. Szczegółowy podział i sposób reagowania opisuje przewodnik [odpowiedzi i błędy](../odpowiedzi-i-bledy.md).

Zasoby globalne pobieraj z `$client`. Dla metod organizacji utwórz najpierw `$shop = $client->forOrganization($organizationId)`. Listy zwracają jedną stronę — iteracja, trwały zapis i punkty kontrolne należą do aplikacji. Operacje POST, PATCH i DELETE nie są automatycznie ponawiane. Po niejednoznacznym błędzie zapisu najpierw uzgodnij stan zewnętrzny.

Przykłady na stronach operacji są celowo krótkie i zakładają klienta utworzonego zgodnie z [instrukcją instalacji](../instalacja.md). Wartości przykładowe są syntetyczne. Operacje zmieniające stan uruchamiaj w Stage tylko po jawnym włączeniu zapisu.
