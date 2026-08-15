# `OffersResource::hints()`

Returns product and GPSR hints for organization offers.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `hints(ProductHintOptions $options): ApiResponse<PageResult<ProductHint>>`.
- Parameters: at least one EAN, MPN, or name; limit `0–30`, offset, and language.
- Result: one hint page.

## Behavior and limits

Constructing options without any hint criterion raises `InvalidRequestException`. Hints are suggestions, not a substitute for product validation. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ProductHintOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$hints = $shop->offers()->hints(new ProductHintOptions(name: 'Example product'))->data;
```
