# `OffersResource::createBatch()`

Wysyła paczkę żądań utworzenia ofert.

## Użycie

- Zakres: organizacja.
- Sygnatura: `createBatch(BatchCreateOffersRequest $request, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parametry: od 1 do 500 `CreateOfferRequest`.

## Zachowanie

`BatchCreateOffersRequest` waliduje rozmiar lokalnie. Każdy wynik jest `CommandHandle` do późniejszego sprawdzenia; POST nie jest ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\BatchCreateOffersRequest;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$commands = $shop->offers()->createBatch(new BatchCreateOffersRequest([$request]))->data;
```
