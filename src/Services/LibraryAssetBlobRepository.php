<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Services\Search\FhirSearchWhereClauseBuilder;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Validators\ProcessingResult;

class LibraryAssetBlobRepository
{
    const TABLE_NAME = "dac_LibraryAssetBlob";

    public function __construct(private SystemLogger $logger)
    {
    }

    public function listAssets($tag = "", $summaryOnly = true): array
    {
        $sql = "SELECT uuid, id, title, type, description, original_creator, creation_date, last_update_date";

        if ($summaryOnly !== true) {
            $sql .= ", content, journal";
        }
        $sql .= " FROM " . self::TABLE_NAME . " WHERE 1=1";

        $params = [];
        if (!empty($tag)) {
            $sql .= " AND id IN (SELECT library_asset_blob_id FROM " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG
                . " WHERE tag_id IN (select id FROM " . TagRepository::TABLE_NAME . " WHERE tag = ?) )";
            $params[] = $tag;
        }
        return $this->getAssetsForQuery($sql, $params);
    }

    public function search($searchParams)
    {
        $processingResult = new ProcessingResult();

        $distinctIds = "SELECT distinct la.id FROM " . self::TABLE_NAME
            . " la LEFT JOIN " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG . " lat ON la.id = lat.library_asset_blob_id LEFT JOIN "
            . TagRepository::TABLE_NAME . " t ON lat.tag_id = t.id ";
        $where = FhirSearchWhereClauseBuilder::build($searchParams);
        $query = $distinctIds . $where->getFragment();

        $ids = QueryUtils::fetchTableColumn($query, 'id', $where->getBoundValues());
        if (empty($ids)) {
            return $processingResult;
        }
        $sql = "SELECT la.uuid, la.id, la.title, la.type, la.description, la.original_creator, la.creation_date, la.last_update_date ";
        if (!empty($searchParams['uuid'])) {
            // we will only return the data if we have an id search parameters
            $sql .= ", la.content, la.journal ";
        }
        $sql .= "FROM " . self::TABLE_NAME . " la ";
        $sql .= "WHERE la.id IN (" . implode(',', array_map('intval', $ids)) . ")";

        $assets = $this->getAssetsForQuery($sql, []);
        foreach ($assets as $asset) {
            $processingResult->addData($asset->jsonSerialize());
        }
        return $processingResult;
    }

    private function getAssetsForQuery($sql, $params)
    {
        $records = QueryUtils::fetchRecords($sql, $params);

        // we need to grab all of our ids as we loop through and generate our objects
        $ids = [];
        foreach ($records as $record) {
            $ids[] = intval($record['id']);
        }
        // now we can fetch our tags
        $tagRepo = new TagRepository();
        $tags = $tagRepo->getTagsForAssetIds($ids);
        $assets = [];
        foreach ($records as $row) {
            $asset = new LibraryAssetBlobDTO();
            $asset->setId($row['id'])
                ->setTitle($row['title'])
                ->setType($row['type'])
                ->setDescription($row['description'])
                ->setContent($row['content'])
                ->setJournal($row['journal'])
                ->setOriginalCreator($row['original_creator'])
                ->setCreationDate($row['creation_date'])
                ->setLastUpdateDate($row['last_update_date'])
                ->setTags($tags[$row['id']] ?? []);

            if (empty($row['uuid'])) {
                $uuid = self::updateLibraryAssetBlobUuid($row['id']);
            } else {
                $uuid = $row['uuid'];
            }
            $asset->setUuid(UuidRegistry::uuidToString($uuid));
            $assets[] = $asset;
        }

        return $assets;
    }

    public function getAsset($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Invalid id');
        }
        $sql = "SELECT uuid, id, title, type, description, original_creator, creation_date, last_update_date, content, journal";
        $sql .= " FROM " . self::TABLE_NAME . " WHERE id = ?";

        $params = [];
        $params[] = $id;
        // should only be one anyways
        $assets = $this->getAssetsForQuery($sql, $params);
        return $assets[0] ?? null;
    }
    public function existsAsset($assetTitle)
    {
        $sql = "SELECT uuid, id FROM " . self::TABLE_NAME . " WHERE title = ?";
        $params = [];
        $params[] = $assetTitle;
        $records = QueryUtils::fetchRecords($sql, $params);
        return !empty($records);
    }

    public function saveLibraryAssetBlob(LibraryAssetBlobDTO $assetBlob, int $userId)
    {
        $sql = "INSERT INTO " . self::TABLE_NAME . " (uuid, title, type, description, original_creator, creator_link, created_by,"
        . " last_updated_by, content, journal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [];
        $registry = self::getUuidRegistry();
        $params[] = $registry->createUuid();
        $params[] = $assetBlob->getTitle();
        $params[] = $assetBlob->getType();
        $params[] = $assetBlob->getDescription();
        $params[] = $assetBlob->getOriginalCreator();
        $params[] = $assetBlob->getCreatorLink();
        $params[] = $userId;
        $params[] = $userId;
        $params[] = $assetBlob->getContent();
        $params[] = $assetBlob->getJournal();
        QueryUtils::sqlStatementThrowException($sql, $params);
        $assetBlob->setId(QueryUtils::getLastInsertId());
        $assetBlobWithTags = $this->saveTags($assetBlob);
        return $assetBlobWithTags;
    }

    public function saveTags(LibraryAssetBlobDTO $assetBlob)
    {
        // seems like the easiest is to delete all the tags, and then relink them
        QueryUtils::fetchRecords("DELETE FROM " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG . " WHERE library_asset_blob_id = ?", [$assetBlob->getId()]);
        if (!empty($assetBlob->getTags())) {
            $tagRepeat = str_repeat('?,', count($assetBlob->getTags()) - 1) . '?';
            $sql = "INSERT INTO " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG . " (library_asset_blob_id, tag_id) SELECT ?, t.id FROM "
                . TagRepository::TABLE_NAME . " t WHERE t.tag IN ("
                . $tagRepeat . ")";
            QueryUtils::sqlStatementThrowException($sql, array_merge([$assetBlob->getId()], $assetBlob->getTags()));

            // some of the tags may not exist so we need to populate only the ones that are there
            $tags = QueryUtils::fetchTableColumn("SELECT tag FROM " . TagRepository::TABLE_NAME
                . " WHERE tag IN (" . $tagRepeat . ")", 'tag', $assetBlob->getTags());
            $assetBlob->setTags($tags);
        }
        return $assetBlob;
    }

    public static function updateLibraryAssetBlobUuid($id)
    {
        $registry = self::getUuidRegistry();
        $uuid = $registry->createUuid();
        $sql = "UPDATE " . self::TABLE_NAME . " SET uuid = ? WHERE id = ?";
        QueryUtils::sqlStatementThrowException($sql, [$uuid, $id]);
        return $uuid;
    }

    public static function getUuidRegistry()
    {
        $registry = new UuidRegistry(['table_name' => self::TABLE_NAME]);
        return $registry;
    }
}
