# `OffersResource::events()`

Fetches one newest-first page of organization offer events.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `events(?OfferEventsOptions $options = null): ApiResponse<list<OfferEvent>>`.
- Parameters: optional older-than `untilId`, event types, limit `0–1000`, and language.
- Result: one ordered event page.

## Behavior and limits

`untilId` excludes that event and newer events. It is not a future cursor; persist a checkpoint time and reconcile after retention gaps. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\ValueObject\EventId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$events = $shop->offers()->events(new OfferEventsOptions(untilId: EventId::fromString('event-id')))->data;
```
