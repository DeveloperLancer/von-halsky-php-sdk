# `OffersResource::create()`

Submits one offer-creation command.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parameters: typed product, stock, price, optional GPSR, external ID, and shipping time.
- Result: accepted command and proposed offer IDs.

## Behavior and limits

HTTP 201 means the command was accepted, not that the offer is ready. `ProductProposal` needs a leaf category and EAN or MPN; shipping time is `0–60`. This POST is never automatically retried. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \DevLancer\VonHalsky\Model\Offer\CreateOfferRequest $request */
$accepted = $shop->offers()->create($request)->data;
$commandId = $accepted->commandId;
```
