# `ReturnsResource::accept()`

Accepts one organization return.

## Use it

- Scope: organization; call `$shop->returns()`.
- Signature: `accept(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ActionResult>`.
- Result: typed action result.

## Behavior and limits

Acceptance changes remote state and is never automatically retried. Verify quantities and the business decision before calling it. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->returns()->accept(ReturnId::fromString('return-id'))->data;
```
