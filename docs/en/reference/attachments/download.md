# `AttachmentsResource::download()`

Downloads an attachment as a response stream.

## Use it

- Scope: organization; call `$shop->attachments()`.
- Signature: `download(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse<DownloadedAttachment>`.
- Result: stream, optional MIME type, filename, and known size.

## Behavior and limits

The SDK does not buffer or close the returned stream. Always close it, including on a failed destination write. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$download = $shop->attachments()->download(OfferId::fromString('offer-id'), AttachmentId::fromString('attachment-id'))->data;
try {
    while (!$download->stream->eof()) {
        $chunk = $download->stream->read(8192);
    }
} finally {
    $download->stream->close();
}
```
