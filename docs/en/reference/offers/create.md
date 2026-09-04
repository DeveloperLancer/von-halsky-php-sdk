# `OffersResource::create()`

Submits one offer-creation command.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parameters: typed product, stock, price, optional GPSR, external ID, and shipping time.
- Result: accepted command and proposed offer IDs.

## Behavior and limits

HTTP 201 means the command was accepted, not that the offer is ready. `ProductProposal` needs a leaf category and EAN or MPN; product name is `7–150` and description is `100–100000` characters. SKU is at most 100 characters. An offer needs 1–20 images with filenames ending in `.jpg`, `.png`, or `.webp`; shipping time is `0–60`. GPSR uses `GpsrInfo::required(new Manufacturer(...), ...)`: manufacturer name and valid email are required, while country, addresses, phone, and structured `ResponsiblePerson` details are optional. This POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$accepted = $shop->offers()->create($request)->data;
$commandId = $accepted->commandId;
```
