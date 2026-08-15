# `OrdersResource::deliveryMethods()`

Reads the current global v2 delivery-method dictionary.

## Use it

- Scope: global; call `$client->orders()`.
- Signature: `deliveryMethods(?ResponseLanguage $language = null): ApiResponse<list<DeliveryMethod>>`.
- Result: typed code and name pairs.

## Behavior and limits

Use this instead of the deprecated v1 delivery dictionary. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$methods = $client->orders()->deliveryMethods()->data;
```
