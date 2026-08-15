# `AttachmentsResource::delete()`

Usuwa załącznik oferty.

## Użycie

- Zakres: organizacja.
- Sygnatura: `delete(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse<null>`.
- Wynik: `null` po udanym usunięciu.

## Zachowanie

DELETE zmienia stan zdalny i nie jest automatycznie ponawiany. Przed wywołaniem potwierdź ID w stanie aplikacji.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$shop->attachments()->delete(OfferId::fromString('offer-id'), AttachmentId::fromString('attachment-id'));
```
