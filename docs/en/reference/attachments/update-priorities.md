# `AttachmentsResource::updatePriorities()`

Updates the display order of attachments for one organization offer.

## Use it

- Scope: organization; call `$shop->attachments()`.
- Signature: `updatePriorities(OfferId $offerId, array $priorities, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parameters: offer ID and a list of `AttachmentPriority`; priority is 1 through 1000 and lower values are displayed first.
- Result: one accepted command handle.

## Behavior and limits

The list order is preserved and the API applies the requested priorities asynchronously. Store the returned `commandId`. This PUT is never automatically retried.

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Attachment\AttachmentPriority;
use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->attachments()->updatePriorities(OfferId::fromString('offer-id'), [
    new AttachmentPriority(AttachmentId::fromString('attachment-id'), 1),
])->data;
```
