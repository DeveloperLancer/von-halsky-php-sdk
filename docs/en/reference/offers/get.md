# `OffersResource::get()`

Reads one offer for the selected organization.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `get(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Result: one typed offer.

## Behavior and limits

The response must be a non-empty object or `ResponseMappingException` is raised. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->get(OfferId::fromString('offer-id'))->data;
```
