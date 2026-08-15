# `AttachmentsResource::delete()`

Deletes one attachment from an organization offer.

## Use it

- Scope: organization; call `$shop->attachments()`.
- Signature: `delete(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse<null>`.
- Result: `null` data on successful deletion.

## Behavior and limits

Deletion changes remote state and is never automatically retried. Confirm the attachment identity in application state first. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$shop->attachments()->delete(OfferId::fromString('offer-id'), AttachmentId::fromString('attachment-id'));
```
