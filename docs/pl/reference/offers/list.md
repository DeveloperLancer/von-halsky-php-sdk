# `OffersResource::list()`

Zwraca jedną stronę ofert organizacji.

## Użycie

- Zakres: organizacja, `$shop->offers()`.
- Sygnatura: `list(?OfferListOptions $options = null): ApiResponse<PageResult<OfferDetails>>`.
- Parametry: statusy, limit `0–30`, offset, dozwolone sortowanie, język.

## Zachowanie

SDK nie iteruje automatycznie po stronach. Po trwałym przetworzeniu strony aplikacja decyduje o następnym offsecie.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OfferListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->offers()->list(new OfferListOptions(limit: 30, sort: ['-updatedAt']))->data;
```
