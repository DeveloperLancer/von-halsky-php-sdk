# `ReturnsResource::list()`

Lists returns for an organization.

## Use it

- Scope: organization; call `$shop->returns()`.
- Signature: `list(?ReturnListOptions $options = null): ApiResponse<PageResult<ReturnDetails>>`.
- Parameters: statuses, limit `0–30`, offset, and language.
- Result: one page of typed returns.

## Behavior and limits

The SDK fetches one page only. Persist and process it according to your application’s retention policy. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ReturnListOptions;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->returns()->list(new ReturnListOptions(statuses: ['ACCEPTED']))->data;
```
