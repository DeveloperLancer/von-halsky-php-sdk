# `AttachmentsResource::list()`

Zwraca jedną stronę metadanych załączników oferty.

## Użycie

- Zakres: organizacja, `$shop->attachments()`.
- Sygnatura: `list(OfferId $offerId, ?AttachmentListOptions $options = null): ApiResponse<PageResult<AttachmentInfo>>`.
- Parametry: ID oferty, limit `0–30`, offset i język.

## Zachowanie

Metoda nie pobiera bajtów pliku i nie iteruje po stronach.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\OfferId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$zalaczniki = $shop->attachments()->list(OfferId::fromString('offer-id'))->data;
```
