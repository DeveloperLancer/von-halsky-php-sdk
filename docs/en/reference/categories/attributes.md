# `CategoriesResource::attributes()`

Reads the offer attribute definitions for one global category.

## Use it

- Scope: global; call `$client->categories()`.
- Signature: `attributes(CategoryId $categoryId, ?ResponseLanguage $language = null): ApiResponse<list<AttributeDefinition>>`.
- Parameters: the category ID and optional response language.
- Result: typed definitions, dictionaries, cardinality, expected values, and optional unit-of-measure details for measurable attributes.

## Behavior and limits

Use a leaf category to prepare an offer. Enum-like response values are forward-compatible: unknown values remain readable. When a definition represents a measurable value such as weight or volume, `unitOfMeasureDetails` is present with `code`, `symbol`, `group`, and a locale-specific `translation`; express the attribute value in that unit. API and transport errors use [shared handling](../../responses-and-errors.md).

## Example

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CategoryId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$attributes = $client->categories()->attributes(CategoryId::fromString('leaf-category-id'))->data;
```
