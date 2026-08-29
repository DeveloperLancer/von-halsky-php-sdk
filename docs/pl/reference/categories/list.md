# `CategoriesResource::list()`

Przegląda ograniczone globalne drzewo kategorii.

## Użycie

- Zakres: globalny, `$client->categories()`.
- Sygnatura: `list(?CategoryTreeOptions $options = null): ApiResponse<list<Category>>`.
- Parametry: głębokość `0–4`, opcjonalny root i język.
- Wynik: węzły drzewa do żądanej głębokości.

## Zachowanie

`children` zawiera tylko dane z tej odpowiedzi i nie powoduje kolejnych żądań. `requireLeaf()` odrzuca `Category`, który nie jest `leaf category`, zgłaszając lokalny `InvalidRequestException`. Sam `CategoryId` nie pozwala SDK potwierdzić, czy wskazuje kategorię bez podkategorii.

## Przykład

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\CategoryTreeOptions;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$tree = $client->categories()->list(new CategoryTreeOptions(depth: 4))->data;
```
