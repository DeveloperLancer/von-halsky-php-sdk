# `ClaimsResource::list()`

Zwraca jedną stronę reklamacji organizacji.

## Użycie

- Zakres: organizacja.
- Sygnatura: `list(?ClaimListOptions $options = null): ApiResponse<PageResult<ClaimDetails>>`.
- Parametry: filtry kontaktowe, statusy, daty UTC, limit `0–30`, offset, sort, język.

## Zachowanie

Filtry i modele mogą zawierać dane osobowe. SDK pobiera jedną stronę; retencję i logowanie kontroluje aplikacja.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ClaimListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$strona = $shop->claims()->list(new ClaimListOptions(states: ['RESOLUTION_IN_PROGRESS'], limit: 30))->data;
```
