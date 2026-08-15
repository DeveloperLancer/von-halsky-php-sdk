# `OffersResource::command()`

Reads one accepted offer-command result.

## Use it

- Scope: organization; call `$shop->offers()`.
- Signature: `command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse<CommandDetails>`.
- Result: status, details, and typed validation errors when present.

## Behavior and limits

This performs exactly one status request. Your application schedules subsequent calls and persists the command ID and acceptance time. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CommandId;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$details = $shop->offers()->command(CommandId::fromString('command-id'))->data;
```
