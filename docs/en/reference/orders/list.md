# `OrdersResource::list()`

Lists orders for an organization.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `list(?OrderListOptions $options = null): ApiResponse<PageResult<OrderDetails>>`.
- Parameters: statuses, payment status, limit `0–30`, offset, supported sort, UTC watermark, and language.
- Result: one page of typed orders.

## Behavior and limits

The SDK makes one page request. Use a durable UTC watermark and persist each page before advancing it. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->orders()->list(new OrderListOptions(paymentStatuses: ['PAID'], limit: 30))->data;
```
