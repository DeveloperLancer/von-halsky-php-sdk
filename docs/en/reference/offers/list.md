# `OffersResource::list()`

Lists offers for the selected organization.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `list(?OfferListOptions $options = null): ApiResponse<PageResult<OfferDetails>>`.
- Parameters: statuses, limit `0–30`, non-negative offset, supported sort values, and language.
- Result: one page of offers and page metadata.

## Behavior and limits

The SDK performs one page request only. Persist and advance an application cursor only after processing the page. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OfferListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->offers()->list(new OfferListOptions(limit: 30, sort: ['-updatedAt']))->data;
```
