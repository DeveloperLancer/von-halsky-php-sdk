# `AttachmentsResource::upload()`

Wysyła strumień pliku do oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `upload(OfferId $offerId, AttachmentType $type, string $filename, string $mimeType, StreamInterface $stream, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parametry: typ, nazwa do 500 bajtów, poprawny typ MIME i strumień PSR-7.

## Zachowanie

SDK czyta strumień bez buforowania i go nie zamyka. Wysłanie jest operacją POST bez automatycznego ponawiania; serwer ostatecznie sprawdza rozmiar i zawartość. Aplikacja powinna wcześniej zastosować własne limity rozmiaru i zasady ochrony przed złośliwymi plikami.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Attachment\AttachmentType;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
/** @var \Psr\Http\Message\StreamInterface $stream */
try {
    $komenda = $shop->attachments()->upload(OfferId::fromString('offer-id'), AttachmentType::MANUAL, 'manual.pdf', 'application/pdf', $stream)->data;
} finally {
    $stream->close();
}
```
