# `ReturnsResource::forOrder()`

Lists returns associated with one organization order.

## Use it

- Scope: organization; call `$shop->returns()`.
- Signature: `forOrder(OrderId $orderId, ?ReturnListOptions $options = null): ApiResponse<PageResult<ReturnDetails>>`.
- Parameters: order ID and optional return filters.
- Result: one page of returns for that order.

## Behavior and limits

The result is paginated even for a specific order. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->returns()->forOrder(OrderId::fromString('order-id'))->data;
```
