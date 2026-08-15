# `AttachmentsResource::download()`

Pobiera załącznik jako strumień odpowiedzi.

## Użycie

- Zakres: organizacja.
- Sygnatura: `download(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse<DownloadedAttachment>`.
- Wynik: strumień, opcjonalny typ MIME, nazwa i rozmiar.

## Zachowanie

SDK nie buforuje ani nie zamyka zwróconego strumienia. Aplikacja zawsze zamyka go w `finally` i zapisuje do miejsca z własnym limitem rozmiaru.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$pobrany = $shop->attachments()->download(OfferId::fromString('offer-id'), AttachmentId::fromString('attachment-id'))->data;
try {
    while (!$pobrany->stream->eof()) {
        $chunk = $pobrany->stream->read(8192);
    }
} finally {
    $pobrany->stream->close();
}
```
