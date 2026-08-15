# `DeprecatedResource::deliveryMethods()`

Reads the legacy global v1 delivery-method codes.

## Use it

- Scope: global; call `$client->deprecated()`.
- Signature: `deliveryMethods(?ResponseLanguage $language = null): ApiResponse<list<string>>`.
- Result: legacy string codes only.

## Behavior and limits

This method is deprecated and planned for removal in SDK 2.0. Use [`OrdersResource::deliveryMethods()`](../orders/delivery-methods.md) for typed v2 code/name pairs. API errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$legacyCodes = $client->deprecated()->deliveryMethods()->data;
```
