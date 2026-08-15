# `ClaimsResource::get()`

Reads a claim belonging to an organization order.

## Use it

- Scope: organization; call `$shop->claims()`.
- Signature: `get(OrderId $orderId, ClaimId $claimId, ?ResponseLanguage $language = null): ApiResponse<ClaimDetails>`.
- Result: one typed claim.

## Behavior and limits

The order ID is part of the claim scope. Inspect the returned state before selecting a resolution action. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ClaimId;
use DevLancer\VonHalsky\ValueObject\OrderId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$claim = $shop->claims()->get(OrderId::fromString('order-id'), ClaimId::fromString('claim-id'))->data;
```
