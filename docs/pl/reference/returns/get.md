# `ReturnsResource::get()`

Zwraca szczegóły jednego zwrotu.

## Użycie

- Zakres: organizacja.
- Sygnatura: `get(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ReturnDetails>`.
- Wynik: `ReturnDetails`.

## Zachowanie

Przed zmianą stanu sprawdź aktualne dane zwrotu. Pusta odpowiedź sukcesu powoduje błąd mapowania.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$return = $shop->returns()->get(ReturnId::fromString('return-id'))->data;
```
