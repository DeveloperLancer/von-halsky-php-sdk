# `ReturnsResource::list()`

Zwraca jedną stronę zwrotów organizacji.

## Użycie

- Zakres: organizacja, `$shop->returns()`.
- Sygnatura: `list(?ReturnListOptions $options = null): ApiResponse<PageResult<ReturnDetails>>`.
- Parametry: statusy, limit `0–30`, offset, język.

## Zachowanie

SDK pobiera tylko jedną stronę. Aplikacja zarządza dalszą iteracją i trwałym przetworzeniem.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ReturnListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->returns()->list(new ReturnListOptions(statuses: ['ACCEPTED']))->data;
```
