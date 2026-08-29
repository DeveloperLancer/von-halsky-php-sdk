# `CategoriesResource::list()`

Browses a bounded global category tree.

## Use it

- Scope: global; call `$client->categories()`.
- Signature: `list(?CategoryTreeOptions $options = null): ApiResponse<list<Category>>`.
- Parameters: `CategoryTreeOptions(depth, root, language)`; the API accepts depth 0 through 10. The current category hierarchy described by the integration guide has five levels, so depth 4 retrieves L1-L5.
- Result: the requested root nodes and only descendants included by that depth.

## Behavior and limits

Traversal of `children` makes no further HTTP calls. `Category::requireLeaf()` raises `InvalidRequestException` for a non-leaf category. Mapping, API, and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\CategoryTreeOptions;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$tree = $client->categories()->list(new CategoryTreeOptions(depth: 4))->data;
```
