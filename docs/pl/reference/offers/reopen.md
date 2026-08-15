# `OffersResource::reopen()`

Wysyła komendę ponownego otwarcia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `reopen(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: przyjęta komenda.

## Zachowanie

Komenda jest asynchroniczna; jej stan sprawdza się później. POST nie jest automatycznie ponawiany.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$komenda = $shop->offers()->reopen(OfferId::fromString('offer-id'))->data;
```
