# `OrdersResource::events()`

Fetches one newest-first page of organization order events.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `events(?OrderEventsOptions $options = null): ApiResponse<list<OrderEvent>>`.
- Parameters: optional older-than `untilId`, types, limit `0–1000`, and language.
- Result: one event page.

## Behavior and limits

`untilId` excludes that event and newer records. Retention recovery requires list reconciliation; see [reliability](../../reliability.md). API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderEventsOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$events = $shop->orders()->events(new OrderEventsOptions(limit: 100))->data;
```
