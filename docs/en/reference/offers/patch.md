# `OffersResource::patch()`

Applies a merge patch to one organization offer.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `patch(OfferId $offerId, PatchOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<OfferDetails>`.
- Parameters: ID and `OptionalValue` wrappers for every supported offer patch field: external ID, product, price, stock, GPSR, shipping time, affiliation URL, images, and post-sale policies.
- Result: the updated offer.

## Behavior and limits

Omission, explicit JSON `null`, and a value differ: use `undefined()`, `null()`, and `of()`. The same distinction applies inside `ProductPatch`, `ProductDimensionsPatch`, `PostSalePatch`, and `PostSalePolicyPatch`, so an update of one nested field does not overwrite its siblings. `null()` is available only for fields whose type permits removing the value.

`product`, `price`, and `stock` are required members of an existing offer, so they may be omitted or updated but cannot be removed with `null`. The same rule applies to the required product fields: `name`, `description`, `brand`, and `categoryId`. Text limits match product creation: 7–150 characters for the name, 100–100000 for the description, 1–100 for the brand, and 1–100 for model and supermodel when supplied.

`externalId` and `product.ean` can only be omitted or assigned a non-null value; this SDK rejects `null` locally. The API allows each identifier to be assigned only when it has not already been set, which the SDK cannot determine without an extra read. The server remains authoritative for that rule. `affiliationProductUrl` is limited to 2048 characters. When assigning a hydrated `Category` in `ProductPatch`, the SDK requires it to be a leaf category; a `CategoryId` is trusted and validated by the server.

Product changes are intended primarily for correcting invalid `PENDING` offers. For `PUBLISHED` offers, the integration guidelines generally recommend updating price and stock; the server may reject or revalidate substantial product changes. The SDK never changes the offer status automatically.

This PATCH is never retried automatically. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\ProductPatch;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$offer = $shop->offers()->patch(OfferId::fromString('offer-id'), new PatchOfferRequest(
    product: OptionalValue::of(new ProductPatch(
        name: OptionalValue::of('Updated product name'),
        description: OptionalValue::of('Updated product description containing complete and current information required by the service for prospective buyers.'),
        categoryId: OptionalValue::of(CategoryId::fromString('leaf-category-id')),
    )),
))->data;
```

This changes only the three supplied product fields. The example is intended primarily for correcting a `PENDING` offer; it does not close, recreate, or reopen the offer.
