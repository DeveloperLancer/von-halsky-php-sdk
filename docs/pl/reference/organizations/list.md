# `OrganizationsResource::list()`

Zwraca organizacje dostępne dla obecnego access tokenu.

## Użycie

- Zakres: globalny, `$client->organizations()`.
- Sygnatura: `list(?OrganizationListOptions $options = null): ApiResponse<list<Organization>>`.
- Parametry: opcjonalny język odpowiedzi.
- Wynik: lista `Organization`.

## Zachowanie

Wybierz ID organizacji jawnie przed pracą na ofertach lub zamówieniach. Pola modelu mogą być `null`, jeśli tak stanowi odpowiedź API. Błędy opisują [zasady wspólne](../wspolne-zasady.md).

## Przykład

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$organizations = $client->organizations()->list()->data;
```
