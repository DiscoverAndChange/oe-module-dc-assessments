<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TagRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed tests for TagRepository against the oe-test-db harness (dac_Tag +
 * dac_LibraryAssetBlobTag). Seeded rows use a 'phptest-' tag prefix and a sentinel
 * asset id, cleaned up in tearDown (the join table cascades on tag delete).
 */
class TagRepositoryTest extends TestCase
{
    private const ASSET_TABLE = 'dac_LibraryAssetBlob';

    protected function tearDown(): void
    {
        parent::tearDown();
        // Deleting tags cascades the join rows (FK ON DELETE CASCADE on tag_id); deleting
        // the seeded asset blobs cascades them too (FK ON DELETE CASCADE on asset id).
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . TagRepository::TABLE_NAME . " WHERE tag LIKE 'phptest-%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . self::ASSET_TABLE . " WHERE title LIKE 'phptest-%'",
            [],
            true
        );
    }

    private function seedAsset(string $title): int
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . self::ASSET_TABLE
            . " (title, description, type, view_count, use_count, original_creator, content, created_by, last_updated_by)"
            . " VALUES (?, '', 'article', 0, 0, 'phptest', '', 1, 1)",
            [$title]
        );
        return (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . self::ASSET_TABLE . " WHERE title = ? ORDER BY id DESC LIMIT 1",
            'id',
            [$title]
        );
    }

    private function seedTag(string $tag): int
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . TagRepository::TABLE_NAME . " (tag) VALUES (?)",
            [$tag]
        );
        /** @var int $id */
        $id = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . TagRepository::TABLE_NAME . " WHERE tag = ? ORDER BY id DESC LIMIT 1",
            'id',
            [$tag]
        );
        return $id;
    }

    private function linkTag(int $assetId, int $tagId): void
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG . " (library_asset_blob_id, tag_id) VALUES (?, ?)",
            [$assetId, $tagId]
        );
    }

    public function testGetTagsForAssetIdsReturnsEmptyForEmptyInput(): void
    {
        $repo = new TagRepository();
        $this->assertSame([], $repo->getTagsForAssetIds([]));
    }

    /**
     * REGRESSION (fixed v0.12.3): ids that are all non-positive/non-numeric are dropped by
     * the filter, which previously left an empty "IN ()" -> SQL syntax error. The method
     * now returns [] without querying.
     */
    public function testGetTagsForAssetIdsReturnsEmptyWhenAllIdsInvalid(): void
    {
        $repo = new TagRepository();
        $this->assertSame([], $repo->getTagsForAssetIds(['abc', -1, 0, 'x']));
    }

    public function testListTagsIncludesSeededTags(): void
    {
        $this->seedTag('phptest-anxiety');
        $this->seedTag('phptest-depression');

        $repo = new TagRepository();
        $tags = $repo->listTags();

        $this->assertContains('phptest-anxiety', $tags);
        $this->assertContains('phptest-depression', $tags);
    }

    public function testGetTagsForAssetIdsGroupsByAsset(): void
    {
        $assetA = $this->seedAsset('phptest-asset-a');
        $assetB = $this->seedAsset('phptest-asset-b');
        $t1 = $this->seedTag('phptest-sleep');
        $t2 = $this->seedTag('phptest-mood');
        $this->linkTag($assetA, $t1);
        $this->linkTag($assetA, $t2);
        $this->linkTag($assetB, $t1);

        $repo = new TagRepository();
        $grouped = $repo->getTagsForAssetIds([$assetA, $assetB]);

        $this->assertArrayHasKey($assetA, $grouped);
        $this->assertArrayHasKey($assetB, $grouped);
        sort($grouped[$assetA]);
        $this->assertSame(['phptest-mood', 'phptest-sleep'], $grouped[$assetA]);
        $this->assertSame(['phptest-sleep'], $grouped[$assetB]);
    }

    public function testGetTagsForAssetIdsFiltersNonPositiveIdsBeforeQuery(): void
    {
        $assetA = $this->seedAsset('phptest-asset-only');
        $t1 = $this->seedTag('phptest-only');
        $this->linkTag($assetA, $t1);

        $repo = new TagRepository();
        // string + non-positive ids are dropped; only the real asset id remains
        $grouped = $repo->getTagsForAssetIds([$assetA, 'abc', -5, 0]);

        $this->assertSame([$assetA], array_keys($grouped));
    }
}
