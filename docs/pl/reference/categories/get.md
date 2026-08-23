# `CategoriesResource::get()`

Zwraca wybraną kategorię oraz ograniczone potomstwo.

## Użycie

- Zakres: globalny.
- Sygnatura: `get(CategoryId $categoryId, ?CategoryDetailsOptions $options = null): ApiResponse<Category>`.
- Parametry: ID, głębokość `0–10`, opcjonalny język.
- Wynik: `Category`.

## Zachowanie

Udana odpowiedź musi zawierać obiekt; puste body powoduje `ResponseMappingException`. Użyj `leaf category` w `ProductProposal`.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\CategoryId;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$category = $client->categories()->get(CategoryId::fromString('category-id'))->data;
```
