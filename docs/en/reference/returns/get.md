# `ReturnsResource::get()`

Reads one organization return.

## Use it

- Scope: organization; call `$shop->returns()`.
- Signature: `get(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse<ReturnDetails>`.
- Result: one typed return.

## Behavior and limits

The successful response must be an object or mapping fails. Validate details before a transition. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$return = $shop->returns()->get(ReturnId::fromString('return-id'))->data;
```
