# `OrganizationsResource::list()`

Lists organizations available to the current access token.

## Use it

- Scope: global; call `$client->organizations()`.
- Signature: `list(?OrganizationListOptions $options = null): ApiResponse<list<Organization>>`.
- Parameters: `OrganizationListOptions` optionally sets `ResponseLanguage`.
- Result: a typed organization list in `data`.

## Behavior and limits

Choose an organization explicitly before merchant-scoped work. Nullable organization fields reflect the response contract. Normal API and transport errors follow [shared error handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$response = $client->organizations()->list();
foreach ($response->data as $organization) {
    $id = $organization->id;
}
```
