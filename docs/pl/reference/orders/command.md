# `OrdersResource::command()`

Odczytuje stan jednego polecenia zamówienia.

## Użycie

- Zakres: organizacja.
- Sygnatura: `command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Wynik: `OrderCommand`.

## Zachowanie

To jedno nieblokujące sprawdzenie. Aplikacja wybiera harmonogram i liczbę kolejnych odczytów.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CommandId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$polecenie = $shop->orders()->command(CommandId::fromString('command-id'))->data;
```
