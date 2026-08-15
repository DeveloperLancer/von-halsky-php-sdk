# `DeprecatedResource::refuseOrder()`

Submits the legacy v1 order-refusal command.

## Use it

- Scope: organization; call `$shop->deprecated()`.
- Signature: `refuseOrder(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Result: one typed order command.

## Behavior and limits

The method is deprecated and planned for SDK 2.0 removal; the supported contract has no replacement. This POST changes remote state and is never retried automatically. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->deprecated()->refuseOrder(OrderId::fromString('order-id'))->data;
```
