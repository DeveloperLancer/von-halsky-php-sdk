# `ClaimsResource::partialRefund()`

Requests a partial-refund claim resolution.

## Use it

- Scope: organization; call `$shop->claims()`.
- Signature: `partialRefund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Result: typed action result.

## Behavior and limits

This POST is a customer-visible financial decision and is never automatically retried. Provide a concise approved description when needed. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\ValueObject\OrderId $orderId */
/** @var \DevLancer\VonHalsky\ValueObject\ClaimId $claimId */
$result = $shop->claims()->partialRefund($orderId, $claimId)->data;
```
