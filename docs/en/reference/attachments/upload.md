# `AttachmentsResource::upload()`

Uploads one file stream to an organization offer.

## Use it

- Scope: organization; call `$shop->attachments()`.
- Signature: `upload(OfferId $offerId, AttachmentType $type, string $filename, string $mimeType, StreamInterface $stream, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parameters: offer ID, closed attachment type, filename up to 500 bytes, an allowed MIME type for that attachment type, and a PSR-7 stream.
- Result: an accepted command handle.

## Behavior and limits

The SDK validates the attachment-type/MIME combination from the integration guide, then streams, consumes, and never closes the supplied stream. The caller closes it. This POST is not retried automatically; size, content, and malware policy remain server- or application-validated.

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Attachment\AttachmentType;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \Psr\Http\Message\StreamInterface $stream */
try {
    $command = $shop->attachments()->upload(OfferId::fromString('offer-id'), AttachmentType::MANUAL, 'manual.pdf', 'application/pdf', $stream)->data;
} finally {
    $stream->close();
}
```
