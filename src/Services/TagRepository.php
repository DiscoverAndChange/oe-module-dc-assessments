<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;

class TagRepository
{
    const TABLE_NAME = "dac_Tag";
    public const TABLE_NAME_LIBRARY_ASSET_JOIN_TAG = "dac_LibraryAssetBlobTag";

    public function getTagsForAssetIds(array $assetIds): array
    {
        // nothing to return if we get an empty array
        if (empty($assetIds)) {
            return [];
        }
        // make sure they are all integers and remove any that are not
        $assetIds = array_filter(array_map('intval', $assetIds), function ($id) {
            return $id > 0; // make sure we only have values greater than 0 for our ids
        });

        $sql = "SELECT labt.library_asset_blob_id, t.tag FROM " . self::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG
            . " labt JOIN " . self::TABLE_NAME . " t ON labt.tag_id = t.id "
        . " WHERE labt.library_asset_blob_id IN (" . implode(',', $assetIds) . ")";
        $records = QueryUtils::fetchRecords($sql, []);
        $tags = [];
        // go through each record and group them by asset_id
        foreach ($records as $record) {
            if (empty($tags[$record['library_asset_blob_id']])) {
                $tags[$record['library_asset_blob_id']] = [];
            }
            $tags[$record['library_asset_blob_id']][] = $record['tag'];
        }
        return $tags;
    }

    public function listTags()
    {
        $tags = QueryUtils::fetchTableColumn("Select tag from " . self::TABLE_NAME, 'tag', []);
        return $tags;
    }
}
