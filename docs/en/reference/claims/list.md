# `ClaimsResource::list()`

Lists claims for an organization.

## Use it

- Scope: organization; call `$shop->claims()`.
- Signature: `list(?ClaimListOptions $options = null): ApiResponse<PageResult<ClaimDetails>>`.
- Parameters: contact filters, resolutions, states, UTC dates, limit `0–30`, offset, sort, and language.
- Result: one claim page.

## Behavior and limits

Contact filters and responses can contain personal data; apply least-privilege storage and logging. The SDK fetches one page only. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ClaimListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->claims()->list(new ClaimListOptions(states: ['RESOLUTION_IN_PROGRESS'], limit: 30))->data;
```
