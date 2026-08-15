# `OrdersResource::accept()`

Submits acceptance for one organization order.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `accept(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Result: an accepted order command.

## Behavior and limits

The state change may be asynchronous. Persist the command ID and inspect `command()` or events later. This POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->orders()->accept(OrderId::fromString('order-id'))->data;
```
