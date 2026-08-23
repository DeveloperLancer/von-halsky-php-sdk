# `OffersResource::command()`

Odczytuje pojedynczy wynik `command` oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse<CommandDetails>`.
- Wynik: status, szczegóły i ewentualne błędy walidacji.

## Zachowanie

To pojedyncze żądanie. Harmonogram kolejnych prób, trwały zapis `commandId` i zasady retencji wyniku należą do aplikacji. Nie zakładaj stałego czasu dostępności wyniku: nie jest on potwierdzoną gwarancją API.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CommandId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$details = $shop->offers()->command(CommandId::fromString('command-id'))->data;
```
