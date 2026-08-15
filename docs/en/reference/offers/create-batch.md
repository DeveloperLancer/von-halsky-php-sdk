# `OffersResource::createBatch()`

Submits a batch of offer-creation commands.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `createBatch(BatchCreateOffersRequest $request, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parameters: one to 500 `CreateOfferRequest` items.
- Result: one command handle for each accepted item.

## Behavior and limits

The batch object validates its size locally. Every handle still needs later command or event observation; POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\BatchCreateOffersRequest;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$commands = $shop->offers()->createBatch(new BatchCreateOffersRequest([$request]))->data;
```
