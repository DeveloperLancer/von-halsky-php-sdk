# `ReturnsResource::reject()`

Rejects one organization return.

## Use it

- Scope: organization; call `$shop->returns()`.
- Signature: `reject(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Result: typed action result.

## Behavior and limits

Rejection changes remote state and is never automatically retried. Verify the current return and record your own rationale. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->returns()->reject(ReturnId::fromString('return-id'))->data;
```
