<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\VonHalskyClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('stage')]
final class PhaseFiveReadOnlySmokeTest extends TestCase
{
    public function testAllPhaseFiveOperationsOnStage(): void
    {
        $token = getenv('VON_HALSKY_STAGE_ACCESS_TOKEN');
        if (!is_string($token) || $token === '') {
            self::markTestSkipped('VON_HALSKY_STAGE_ACCESS_TOKEN is not configured.');
        }
        $client = VonHalskyClient::create(
            new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('2099-01-01T00:00:00Z'))),
            Environment::stage(),
        );

        self::assertNotEmpty($client->organizations()->list()->data);
        $tree = $client->categories()->list(new CategoryTreeOptions(depth: 4))->data;
        self::assertNotEmpty($tree);

        $leaf = self::firstLeaf($tree);
        self::assertNotNull($leaf, 'The Stage category tree did not contain a leaf in the requested depth.');
        $details = $client->categories()->get($leaf->id)->data;
        self::assertTrue($details->leaf);
        $client->categories()->attributes($details->id);
        self::addToAssertionCount(1);
    }

    /** @param list<Category> $categories */
    private static function firstLeaf(array $categories): ?Category
    {
        foreach ($categories as $category) {
            if ($category->leaf) {
                return $category;
            }
            $leaf = self::firstLeaf($category->children);
            if ($leaf !== null) {
                return $leaf;
            }
        }

        return null;
    }
}
