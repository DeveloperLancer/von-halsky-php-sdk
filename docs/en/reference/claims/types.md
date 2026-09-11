# `ClaimsResource::types()`

Reads the global localized claim-type dictionary.

## Use it

- Scope: global; call `$client->claims()`.
- Signature: `types(?ResponseLanguage $language = null): ApiResponse<list<ClaimType>>`.
- Result: typed claim IDs and names.

## Behavior and limits

This is the only claim operation without an organization context. API and transport errors use [shared handling](../../responses-and-errors.md).

The published OpenAPI 1.6.9 contract describes a JSON object `{ "data": [{ "id", "description" }] }`. Live Stage (`GET /v1/orders/claim-types`, observed 2026-09-11) returns a JSON array `[{ "id", "description", "code" }]` instead. That is a platform envelope that diverges from the official specification, not an SDK modeling error. The SDK accepts both shapes so `types()` works against Stage and against the documented object. The extra Stage `code` field is kept on `ClaimType::additionalData()`. Production was not compared.

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$types = $client->claims()->types()->data;
```
