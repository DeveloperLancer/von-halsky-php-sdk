# `OffersResource::reopen()`

Submits an organization offer-reopen command.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `reopen(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Result: an accepted command handle.

## Behavior and limits

The command is asynchronous; inspect its later status or events. This POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->reopen(OfferId::fromString('offer-id'))->data;
```
