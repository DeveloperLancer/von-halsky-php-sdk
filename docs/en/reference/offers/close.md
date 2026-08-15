# `OffersResource::close()`

Submits an organization offer-close command.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `close(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Result: an accepted command handle.

## Behavior and limits

The offer can change state after the response; query `command()` or events later. This POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->close(OfferId::fromString('offer-id'))->data;
```
