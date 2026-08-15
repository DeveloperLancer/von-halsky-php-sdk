# `OffersResource::updatePrices()`

Submits typed price updates for organization offers.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `updatePrices(list<OfferPriceUpdate> $updates, ?ResponseLanguage $language = null): ApiResponse<list<CommandHandle>>`.
- Parameters: a non-empty ordered list of offer ID and `Money` updates.
- Result: accepted command handles.

## Behavior and limits

An empty list raises `InvalidRequestException`; each command remains asynchronous. This PATCH is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$commands = $shop->offers()->updatePrices([new OfferPriceUpdate(OfferId::fromString('offer-id'), Money::fromDecimal('44.99'))])->data;
```
