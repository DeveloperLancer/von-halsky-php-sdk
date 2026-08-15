# `OffersResource::command()`

Odczytuje pojedynczy wynik polecenia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse<CommandDetails>`.
- Wynik: status, szczegóły i ewentualne błędy walidacji.

## Zachowanie

To pojedyncze żądanie. Harmonogram kolejnych prób, trwały zapis ID i zasady retencji wyniku należą do aplikacji. Czas dostępności polecenia wymaga potwierdzenia w Stage.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CommandId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$szczegoly = $shop->offers()->command(CommandId::fromString('command-id'))->data;
```
