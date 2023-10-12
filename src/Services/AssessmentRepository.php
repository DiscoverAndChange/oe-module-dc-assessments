<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSummary;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Services\Search\FhirSearchWhereClauseBuilder;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Validators\ProcessingResult;

class AssessmentRepository
{
    const TABLE_NAME = "dac_AssessmentBlob";

    const TABLE_VIEW_CURRENT_ASSESSMENT = "dac_view_current_assessments";

    public function __construct(private SystemLogger $logger)
    {
    }
    public function getAssessmentSummaryList(?int $companyId): array
    {
        // TODO: stephen not sure I like this as it implicitly assumes that the highest autoincrement id
        // is the most recent assessment (based on date)... stephen is there a better way to handle this??
        $params = [];
        $query = "SELECT ab1.uuid, ab1.id, ab1.uid, ab1.name, ab1.date, ab1.company_id, ab1.description, ab1.status
            FROM " . self::TABLE_NAME . " ab1
            WHERE ab1.id IN (
                SELECT max(id)
                FROM " . self::TABLE_NAME . "
                WHERE status = 'published'
                GROUP BY uid
            )";
        if (!empty($companyId)) {
            $query .= " AND (
                ab1.company_id IS NULL or ab1.company_id = ?
            ) ";
            $params[] = $companyId;
        }
        $query .= " ORDER BY ab1.name";
        $assessmentList = QueryUtils::fetchRecords($query, $params);
        return $this->getAssessmentSummaryFromRecords($assessmentList);
    }

    public function search($openEMRSearchParameters): ProcessingResult
    {
        $processingResult = new ProcessingResult();

        $sql = "SELECT ab1.uuid, ab1.id, ab1.uid, ab1.name, ab1.date, ab1.company_id, ab1.description, ab1.status ";
        if (!empty($openEMRSearchParameters['uuid'])) {
            // we will only return the data if we have an id search parameters
            $sql .= ", ab1.data ";
        }
        $sql .= "
            FROM " . self::TABLE_NAME . " ab1 ";

        $publishedToken = new TokenSearchField('status', 'published');
        $openEMRSearchParameters['status'] = $publishedToken;
        $where = FhirSearchWhereClauseBuilder::build($openEMRSearchParameters);
        $query = $sql . $where->getFragment();

        $records = QueryUtils::fetchRecords($query, $where->getBoundValues());
        foreach ($records as $record) {
            // processing data assumes its an array ... annoying
            $processingResult->addData($this->hydrateAssessmentSummaryFromDatabaseRecord($record)->jsonSerialize());
        }
        return $processingResult;
    }

    private function getAssessmentSummaryFromRecords($assessmentList)
    {
        $records = [];
        foreach ($assessmentList as $record) {
            $records[] = $this->hydrateAssessmentSummaryFromDatabaseRecord($record);
        }
        return $records;
    }

    private function hydrateAssessmentSummaryFromDatabaseRecord($record)
    {
        /**
         * return {
        uid: i.uid
        ,name: i.name
        ,description: i.description
        ,date: i.date
        ,isPublic: i.company_id === null
         */
        $result = new AssessmentSummary();
        if (empty($record['uuid'])) {
            // lazy populate these
            $uuid = $this->updateAssessmentUuid($record['id']);
        } else {
            $uuid = $record['uuid'];
        }
        $result->uuid = UuidRegistry::uuidToString($uuid);
        $result->uid = $record['uid'] ?? '';
        $result->name = $record['name'] ?? '';
        $result->description = $record['description'] ?? '';
        $result->data = $record['data'] ?? '';
        $result->date = \DateTime::createFromFormat('Y-m-d H:i:s.u', $record['date'] ?? '');
        $result->isPublic = empty($record['company_id']);
        return $result;
    }

    public function getAssessmentForAssignmentItem(string $assignmentItemUuid, $uid, string $clientID)
    {
        if (empty($uid)) {
            throw new \InvalidArgumentException("Missing uid", ErrorCode::VALIDATE_DATA_MISSING);
        }
        if (empty($clientID)) {
            throw new \InvalidArgumentException("Missing clientID", ErrorCode::VALIDATE_DATA_MISSING);
        }
        if (empty($assignmentItemUuid)) {
            throw new \InvalidArgumentException("Missing assignmentUuid", ErrorCode::VALIDATE_DATA_MISSING);
        }
        // TODO: @adunsulag need to validate against $clientId
        $sql = "SELECT assessment.id,assessment.data,assessment.uid, ab1.status "
            . "FROM " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . " item "
            . "LEFT JOIN " . self::TABLE_NAME . " assessment ON item.assessmentblob_id = assessment.id "
            . "LEFT JOIN " . AssignmentRepository::TABLE_NAME . " assignment ON assignment.id = item.assignment_id AND (assignment.client_id = ? OR assignment.client_id IS NULL)"
            . "WHERE item.uuid = ? AND (assessment.uid = ? OR assessment.uid IS NULL)";
        $params = [$clientID, UuidRegistry::uuidToBytes($assignmentItemUuid), $uid];
        $assessment = $this->getAssessmentFromSQL($sql, $params);
        return $assessment;
    }

    public function getAssessmentForVersion($uid, int $version)
    {
        if (empty($uid)) {
            throw new \InvalidArgumentException("Missing uid", ErrorCode::VALIDATE_DATA_MISSING);
        }
        if (empty($version)) {
            throw new \InvalidArgumentException("Missing version", ErrorCode::VALIDATE_DATA_MISSING);
        }
        $sql = "SELECT id, uid, data FROM " . self::TABLE_NAME . " WHERE id = ? AND uid = ? AND status = 'published'";
        $params = [$version, $uid];
        return $this->getAssessmentFromSQL($sql, $params);
    }

    public function getMostRecentAssessmentIdForUid($uid)
    {
        $sql = "SELECT id FROM " . self::TABLE_VIEW_CURRENT_ASSESSMENT . " WHERE uid = ? LIMIT 1";
        return QueryUtils::fetchSingleValue($sql, 'id', [$uid]);
    }

    public function getAssessmentForUid($uid)
    {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE uid = ? AND status = 'published' ORDER BY date DESC LIMIT 1";
        return $this->getAssessmentFromSQL($sql, [$uid]);
    }

    private function getAssessmentFromSQL($sql, $params)
    {
        $result = QueryUtils::fetchRecords($sql, $params);
        if (empty($result[0])) {
            throw new \InvalidArgumentException("Assessment not found", ErrorCode::RECORD_NOT_FOUND);
        }
        $blobData = json_decode($result[0]['data'], true);
        $blobData['_version'] = $result[0]['id'];
        $blobData['_id'] = $result[0]['id'];
        if (empty($result[0]['uuid'])) {
            // let's lazy update it.
            $uuid = $this->updateAssessmentUuid($result[0]['id']);
        } else {
            $uuid = $result[0]['uuid'];
        }
        $blobData['uuid'] = UuidRegistry::uuidToString($uuid);
        return $blobData;
    }

    public function createAssessment(string $uid, string $name, string $description, array $jsonData, ?int $companyId)
    {
        $htmlSanitizer = new HTMLSanitizer();
        $description = $htmlSanitizer->sanitize($description);
        $name = $htmlSanitizer->sanitize($name);
        $uid = $htmlSanitizer->sanitize($uid);

        // TODO: @adunsulag need to sanitize question prompts, ranges, etc.
        $sql = "INSERT INTO " . self::TABLE_NAME . " (uuid, uid, name, description, data, company_id) VALUES (?, ?, ?, ?, ?, ?)";
        $registry = $this->getUuidRegistry();
        $uuid = $registry->createUuid();
        $params = [$uuid, $uid, $name, $description, json_encode($jsonData), $companyId];
        QueryUtils::sqlStatementThrowException($sql, $params);
        return QueryUtils::getLastInsertId();
    }

    public function existsAssessment(mixed $uid)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM " . self::TABLE_NAME . " WHERE uid = ?";
        $params = [$uid];
        return QueryUtils::fetchSingleValue($sql, 'cnt', $params) > 0;
    }

    public function canEditAssessment($id, ?int $companyId)
    {
        if (empty($companyId)) {
            // super user return true
            return true;
        }
        $sql = "SELECT DISTINCT company_id FROM " . self::TABLE_NAME . " WHERE id = ? ";
        $assessmentCompanyId = QueryUtils::fetchSingleValue($sql, 'company_id', [$id]);
        return $assessmentCompanyId === null || intval($assessmentCompanyId) === $companyId;
    }

    public static function updateAssessmentUuid($id)
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
