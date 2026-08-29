# `CategoriesResource::get()`

Reads one global category and a bounded descendant subtree.

## Use it

- Scope: global; call `$client->categories()`.
- Signature: `get(CategoryId $categoryId, ?CategoryDetailsOptions $options = null): ApiResponse<Category>`.
- Parameters: category ID and optional depth 0 through 4 plus language.
- Result: one hydrated `Category` in `data`.

## Behavior and limits

The response must contain an object; an empty successful body raises `ResponseMappingException`. Descendants are limited to the requested depth. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\CategoryDetailsOptions;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$category = $client->categories()->get(CategoryId::fromString('category-id'), new CategoryDetailsOptions(depth: 2))->data;
```
