# `CategoriesResource::attributes()`

Zwraca definicje atrybutów dla kategorii.

## Użycie

- Zakres: globalny.
- Sygnatura: `attributes(CategoryId $categoryId, ?ResponseLanguage $language = null): ApiResponse<list<AttributeDefinition>>`.
- Wynik: definicje, słowniki i oczekiwane wartości.

## Zachowanie

Przed utworzeniem oferty używaj `leaf category`. Nieznane wartości enumów są zachowywane, aby nowa wartość serwera nie zablokowała hydratacji.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CategoryId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$attributes = $client->categories()->attributes(CategoryId::fromString('leaf-category-id'))->data;
```
