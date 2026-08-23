# `OffersResource::reopen()`

Wysyła żądanie ponownego otwarcia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `reopen(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: `CommandHandle`.

## Zachowanie

`CommandHandle` jest asynchroniczny; jego stan sprawdza się później. POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->reopen(OfferId::fromString('offer-id'))->data;
```
