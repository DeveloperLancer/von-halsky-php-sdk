# `ReturnsResource::reject()`

Odrzuca zwrot.

## Użycie

- Zakres: organizacja.
- Sygnatura: `reject(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Wynik: `ActionResult`.

## Zachowanie

Odrzucenie zmienia zewnętrzny stan i nie jest automatycznie ponawiane. Uzasadnienie oraz audyt decyzji należą do aplikacji.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->returns()->reject(ReturnId::fromString('return-id'))->data;
```
