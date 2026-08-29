# `AttachmentsResource::updatePriorities()`

Aktualizuje kolejność wyświetlania załączników oferty.

## Użycie

- Zakres: organizacja, `$shop->attachments()`.
- Sygnatura: `updatePriorities(OfferId $offerId, array $priorities, ?ResponseLanguage $language = null): ApiResponse<CommandHandle>`.
- Parametry: ID oferty i lista `AttachmentPriority`; priorytet mieści się w zakresie 1-1000, a niższa wartość oznacza wcześniejsze wyświetlenie.
- Wynik: przyjęty `CommandHandle`.

## Zachowanie

Kolejność listy jest zachowana, a API stosuje priorytety asynchronicznie. Zapisz zwrócony `commandId`. Operacja PUT nie jest automatycznie ponawiana.

## Przykład

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
