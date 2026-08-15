# `OffersResource::updateAttributes()`

Submits ordered attribute operations for one organization offer.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `updateAttributes(OfferId $offerId, OfferAttributesPatch $patch, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parameters: offer ID and a non-empty ordered list of upsert/remove operations.
- Result: one accepted command handle.

## Behavior and limits

Operation order is preserved; when the same attribute ID occurs more than once, the API applies the last operation. This PATCH is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\RemoveAttribute;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->offers()->updateAttributes(OfferId::fromString('offer-id'), new OfferAttributesPatch([new RemoveAttribute('attribute-id')]))->data;
```
