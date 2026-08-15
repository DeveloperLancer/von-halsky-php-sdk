# `ReturnsResource::accept()`

Akceptuje zwrot.

## Użycie

- Zakres: organizacja.
- Sygnatura: `accept(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Wynik: `ActionResult`.

## Zachowanie

Akceptacja zmienia zewnętrzny stan i nie jest automatycznie ponawiana. Zweryfikuj ilości i decyzję biznesową.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$wynik = $shop->returns()->accept(ReturnId::fromString('return-id'))->data;
```
