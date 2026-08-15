# `OrdersResource::get()`

Reads one order in an organization.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `get(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderDetails>`.
- Result: one typed order model.

## Behavior and limits

Order models may contain personal data. Retain and log only the fields required for your integration. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$order = $shop->orders()->get(OrderId::fromString('order-id'))->data;
```
