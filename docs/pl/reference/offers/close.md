# `OffersResource::close()`

Wysyła żądanie zamknięcia oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `close(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Wynik: `CommandHandle`.

## Zachowanie

Zmiana może zakończyć się po odpowiedzi — odczytaj później `command()` lub zdarzenia. POST nie jest automatycznie ponawiany; po niejednoznacznym błędzie sieciowym najpierw odczytaj stan oferty.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->close(OfferId::fromString('offer-id'))->data;
```
