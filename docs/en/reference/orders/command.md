# `OrdersResource::command()`

Reads one organization order-command result.

## Use it

- Scope: organization; call `$shop->orders()`.
- Signature: `command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse<OrderCommand>`.
- Result: one typed command result.

## Behavior and limits

This is a single non-blocking status request. Application jobs decide whether and when to ask again. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CommandId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$command = $shop->orders()->command(CommandId::fromString('command-id'))->data;
```
