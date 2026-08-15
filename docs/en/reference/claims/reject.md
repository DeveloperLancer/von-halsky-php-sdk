# `ClaimsResource::reject()`

Rejects a claim for an organization order.

## Use it

- Scope: organization; call `$shop->claims()`.
- Signature: `reject(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Parameters: order and claim IDs; optional description up to 1,000 bytes.
- Result: typed action result.

## Behavior and limits

This POST can be customer-visible and is never retried automatically. Persist the reason in your application if required. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\PostSale\ResolutionDescription;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$result = $shop->claims()->reject($orderId, $claimId, new ResolutionDescription('Claim rejected after review.'))->data;
```
