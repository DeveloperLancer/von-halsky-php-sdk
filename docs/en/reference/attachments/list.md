# `AttachmentsResource::list()`

Lists attachments for one organization offer.

## Use it

- Scope: organization; call `$shop->attachments()`.
- Signature: `list(OfferId $offerId, ?AttachmentListOptions $options = null): ApiResponse<PageResult<AttachmentInfo>>`.
- Parameters: offer ID plus limit `0–30`, offset, and language.
- Result: one page of attachment metadata.

## Behavior and limits

The SDK fetches one page only. It does not download file bytes. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$attachments = $shop->attachments()->list(OfferId::fromString('offer-id'))->data;
```
