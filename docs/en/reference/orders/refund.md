# `OrdersResource::refund()`

Requests a full or exact partial refund for an organization order.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `refund(OrderId $orderId, ?RefundRequest $request = null, ?ResponseLanguage $language = null): ApiResponse<RefundResult>`.
- Parameters: omit the request for full refund; pass `RefundRequest` with `Money` for an exact partial amount.
- Result: typed refund result.

## Behavior and limits

This can be financially consequential. Verify the current order and business decision before calling it. The POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Order\RefundRequest;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->orders()->refund(OrderId::fromString('order-id'), new RefundRequest(Money::fromDecimal('12.34')))->data;
```
