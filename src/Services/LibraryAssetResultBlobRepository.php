<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\FhirSearchWhereClauseBuilder;
use OpenEMR\Validators\ProcessingResult;
use Ramsey\Uuid\Uuid;

class LibraryAssetResultBlobRepository
{
    const TABLE_NAME = "dac_LibraryAssetResultBlob";

    public function __construct(private SystemLogger $logger, private CryptoGen $cryptoGen)
    {
    }

    public function saveLibraryAssetResultBlob(
        LibraryAssetBlobResultDTO $resultBlob,
        LibraryAssetBlobDTO $asset,
        $clientUuid
    ) {
        $answersToSave = null;
        $journalToSave = null;
        // if we are a new object let's generate an id for it.
        if (empty($resultBlob->getId())) {
            $resultBlob->generateId();
        }
        $sanitizer = new HTMLSanitizer();
        if ($asset->getType() != 'article') {
            // need to sanitize and encrypt everything
            $answers = $resultBlob->getAnswers();

            $cleanedAnswers = array_map(function ($answer) use ($sanitizer) {
                if (!empty($answer['value'])) {
                    $answer['value'] = $sanitizer->sanitize($answer['value']);
                }
                return $answer;
            }, $answers);
            if (!empty($cleanedAnswers)) {
                $answersToSave = json_encode($cleanedAnswers);
            }


            if ($this->shouldEncrypt()) {
                $answersToSave = !empty($answersToSave) ? $this->cryptoGen->encryptStandard($answersToSave) : null;
            }
        }
        if (!empty($resultBlob->getJournal())) {
            $cleanJournal = $sanitizer->sanitize($resultBlob->getJournal());
            if (!empty($cleanJournal)) {
                $journalToSave = $this->cryptoGen->encryptStandard($cleanJournal);
            }
        }
        $sql = "INSERT INTO " . self::TABLE_NAME . " (id, answers, journal_entry, creation_date, asset_id, client_id) "
            . " VALUES (?, ?, ?, ?, ?, (select pid FROM patient_data WHERE patient_data.uuid = ?))";
        $params = [];
        $params[] = $resultBlob->getId();
        $params[] = $answersToSave;
        $params[] = $journalToSave;
        $params[] = $resultBlob->getCreationDate()->format("Y-m-d H:i:s.u");
        $params[] = $asset->getId();
        $params[] = $clientUuid;
        QueryUtils::sqlStatementThrowException($sql, $params);

        $updatedDTO = clone $resultBlob;
        // as we encrypt all of this, we are going to clear it out before returning to keep it consistent with the prior
        // api expectations
        $updatedDTO->setJournal(null);
        $updatedDTO->setAnswers([]);
        return $updatedDTO;
    }

    public function search($searchParams)
    {
        $processingResult = new ProcessingResult();

        $distinctIds = "SELECT DISTINCT larb.id ";
        $fromClause = " FROM " . self::TABLE_NAME . " larb "
            . " LEFT JOIN (SELECT patient_data.pid AS patient_pid, patient_data.uuid AS patient_uuid FROM " . PatientService::TABLE_NAME . ") pd ON pd.patient_pid = larb.client_id "
            . " LEFT JOIN (select id AS assignmentitem_id, assetresultblob_id FROM " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . ") ai ON ai.assetresultblob_id = larb.id ";

        $where = FhirSearchWhereClauseBuilder::build($searchParams);
        $query = $distinctIds . $fromClause . $where->getFragment();

        $ids = QueryUtils::fetchTableColumn($query, 'id', $where->getBoundValues());
        if (empty($ids)) {
            return $processingResult;
        }
        $sql = "SELECT larb.id, larb.answers, larb.journal_entry, larb.creation_date, larb.asset_id, larb.client_id "
            . ", ai.assignmentitem_id, pd.patient_uuid"
            . $fromClause;
        $bindClause = str_repeat("?,", count($ids) - 1) . "?";
        $sql .= "WHERE larb.id IN (" . $bindClause . ")";

        $results = $this->getRecordsForQuery($sql, $ids);
        foreach ($results as $blob) {
            $processingResult->addData($blob->jsonSerialize());
        }
        return $processingResult;
    }

    public function saveTags(LibraryAssetBlobDTO $assetBlob)
    {
        // seems like the easiest is to delete all the tags, and then relink them
        QueryUtils::fetchRecords("DELETE FROM " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG . " WHERE library_asset_blob_id = ?", [$assetBlob->getId()]);
        if (!empty($assetBlob->getTags())) {
            $tagRepeat = str_repeat('?,', count($assetBlob->getTags()) - 1) . '?';
            $sql = "INSERT INTO " . TagRepository::TABLE_NAME_LIBRARY_ASSET_JOIN_TAG
                . " (library_asset_blob_id, tag_id) SELECT ?, t.id FROM " . TagRepository::TABLE_NAME . " t WHERE t.tag IN ("
                . $tagRepeat . ")";
            QueryUtils::sqlStatementThrowException($sql, array_merge([$assetBlob->getId()], $assetBlob->getTags()));

            // some of the tags may not exist so we need to populate only the ones that are there
            $tags = QueryUtils::fetchTableColumn("SELECT t.tag FROM " . TagRepository::TABLE_NAME . " t WHERE t.tag IN (" . $tagRepeat . ")", 'tag', $assetBlob->getTags());
            $assetBlob->setTags($tags);
        }
        return $assetBlob;
    }

    private function shouldEncrypt()
    {
        // TODO: @adunsulag we may want to make this configurable, but for now we will just encrypt everything
        // the original default was to have encryption on always.
        return true;
    }

    public function getDecryptedAssetResultBlob(string $id, ?int $pid = null)
    {
        $sql = "SELECT larb.id, larb.answers, larb.journal_entry, larb.creation_date, larb.asset_id, larb.client_id "
            . ", ai.id AS assignmentitem_id, pd.patient_uuid"
        . " FROM " . self::TABLE_NAME . " larb "
        . " LEFT JOIN (SELECT pid AS patient_pid, uuid AS patient_uuid FROM " . PatientService::TABLE_NAME . ") pd ON pd.pid = larb.client_id "
        . " LEFT JOIN " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . " ai ON ai.assetresultblob_id = larb.id WHERE larb.id = ?";
        $params = [$id];
        if (!empty($pid)) {
            $sql .= " AND client_id = ?";
            $params[] = $pid;
        }

        $results = $this->getRecordsForQuery($sql, $params);
        return $results[0];
    }

    /**
     * @param $sql
     * @param $params
     * @return LibraryAssetBlobResultDTO[]
     */
    private function getRecordsForQuery($sql, $params)
    {
        $records = QueryUtils::fetchRecords($sql, $params);
        if (empty($records)) {
            return null;
        }
        $results = [];
        foreach ($records as $record) {
            $results[] = $this->hydrateResultBlobFromRecord($record);
        }
        return $results;
    }

    private function hydrateResultBlobFromRecord($record)
    {
        $blob = new LibraryAssetBlobResultDTO();
        $blob->setId($record['id']);
        $blob->setAssetId($record['asset_id']);
        $blob->setAssignmentItemId($record['assignmentitem_id']);
        $answers = $record['answers'];
        $journal = $record['journal_entry'];
        if ($this->shouldEncrypt()) {
            if (!empty($answers)) {
                $answers = $this->cryptoGen->decryptStandard($answers);
            }
            if (!empty($journal)) {
                $journal = $this->cryptoGen->decryptStandard($journal);
            }
        }
        $blob->setAnswers(!empty($answers) ? json_decode($answers, true) : []);
        $blob->setJournal($journal);
        if (!empty($record['patient_uuid'])) {
            $blob->setClientId(UuidRegistry::uuidToString($record['patient_uuid']));
        }
        $dateFormat = "Y-m-d H:i:s.u";
        $blob->setCreationDate(\DateTime::createFromFormat($dateFormat, $record['creation_date']));
        return $blob;
    }
}
