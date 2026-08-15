# `OffersResource::depositTypes()`

Reads the global deposit dictionary.

## Use it

- Scope: global; call `$client->offers()`.
- Signature: `depositTypes(?ResponseLanguage $language = null): ApiResponse<list<DepositType>>`.
- Result: deposit IDs, names, and typed prices.

## Behavior and limits

This is the sole offer operation allowed without an organization context. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$depositTypes = $client->offers()->depositTypes()->data;
```
