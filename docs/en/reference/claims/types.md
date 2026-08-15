# `ClaimsResource::types()`

Reads the global localized claim-type dictionary.

## Use it

- Scope: global; call `$client->claims()`.
- Signature: `types(?ResponseLanguage $language = null): ApiResponse<list<ClaimType>>`.
- Result: typed claim IDs and names.

## Behavior and limits

This is the only claim operation without an organization context. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$types = $client->claims()->types()->data;
```
