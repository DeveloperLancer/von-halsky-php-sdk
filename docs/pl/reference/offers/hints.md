# `OffersResource::hints()`

Zwraca podpowiedzi produktu i GPSR.

## Użycie

- Zakres: organizacja.
- Sygnatura: `hints(ProductHintOptions $options): ApiResponse<PageResult<ProductHint>>`.
- Parametry: przynajmniej EAN, MPN lub nazwa; limit `0–30`, offset i język.

## Zachowanie

Brak wszystkich kryteriów powoduje lokalny `InvalidRequestException`. Podpowiedzi nie zastępują walidacji produktu.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ProductHintOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$hints = $shop->offers()->hints(new ProductHintOptions(name: 'Example product'))->data;
```
