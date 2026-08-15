# `OffersResource::updateStocks()`

Submits typed stock updates for organization offers.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `updateStocks(list<OfferStockUpdate> $updates, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parameters: a non-empty ordered list of offer ID and `Stock` updates.
- Result: accepted command handles.

## Behavior and limits

An empty list raises `InvalidRequestException`; stock construction validates its quantity. This PATCH is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$commands = $shop->offers()->updateStocks([new OfferStockUpdate(OfferId::fromString('offer-id'), new Stock(25))])->data;
```
