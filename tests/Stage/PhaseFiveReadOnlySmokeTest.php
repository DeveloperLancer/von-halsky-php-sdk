<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseFiveReadOnlySmokeTest extends StageTestCase
{
    public function testAllPhaseFiveOperationsOnStage(): void
    {
        $client = $this->stageClient();

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
