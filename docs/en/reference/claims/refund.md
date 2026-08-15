# `ClaimsResource::refund()`

Requests a refund claim resolution.

## Use it

- Scope: organization; call `$shop->claims()`.
- Signature: `refund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Result: typed action result.

## Behavior and limits

This POST can have financial effects and is never automatically retried. Confirm current claim state and application authorization first. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$result = $shop->claims()->refund($orderId, $claimId)->data;
```
