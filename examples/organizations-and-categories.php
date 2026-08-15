<?php

declare(strict_types=1);

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\VonHalskyClient;

require dirname(__DIR__) . '/vendor/autoload.php';

$token = getenv('VON_HALSKY_ACCESS_TOKEN');
if (!is_string($token) || $token === '') {
    throw new RuntimeException('Set VON_HALSKY_ACCESS_TOKEN before running this Stage read-only example.');
}

$client = VonHalskyClient::create(
    new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('+5 minutes'))),
    Environment::stage(),
);

$organization = $client->organizations()->list()->data[0] ?? null;
if ($organization?->id !== null) {
    $context = $client->forOrganization($organization->id);
    fwrite(STDOUT, 'Selected organization ' . $context->organizationId->value . PHP_EOL);
}

/** @param list<Category> $categories */
function walkCategories(array $categories): void
{
    foreach ($categories as $category) {
        fwrite(STDOUT, str_repeat('  ', count($category->relations)) . $category->name . PHP_EOL);
        walkCategories($category->children);
    }
}

walkCategories($client->categories()->list(new CategoryTreeOptions(depth: 4))->data);
