# `OffersResource::patch()`

Applies a merge patch to one organization offer.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `patch(OfferId $offerId, PatchOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Parameters: ID and `OptionalValue` wrappers for price, stock, GPSR, and shipping time.
- Result: the updated offer.

## Behavior and limits

Omission, explicit JSON `null`, and a value differ: use `undefined()`, `null()`, and `of()`. This PATCH is never retried automatically. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->patch(OfferId::fromString('offer-id'), new PatchOfferRequest(stock: OptionalValue::null()))->data;
```
