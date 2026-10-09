<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentGroupValidator;
use OpenEMR\Services\BaseService;
use OpenEMR\Services\FacilityService;

class AssessmentGroupService extends BaseService
{
    const TABLE_NAME = "dac_AssessmentGroup";

    const TABLE_GROUP_JOIN_TABLE = "dac_AssessmentGroupPermission";

    const ASSESSMENT_BLOB_JOIN_TABLE_NAME = "dac_AssessmentGroupAssessmentBlob";

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME);
    }

    /**
     * @param mixed $getCompanyId
     * @return array<mixed>
     */
    public function getAllGroups(bool $showAllGroups, $getCompanyId)
    {
        $sql = "SELECT
                    ag.id, ag.name, ag.date_created, ag.date_updated, ab.name AS assessmentblob_name, ab.uid
                    , agab.assessmentblob_id, agab.assessmentgroup_id, agab.display_order
                    , f.id AS company_id, f.name AS company_name
                FROM " . self::TABLE_NAME . " ag
                LEFT JOIN facility f ON ag.company_id = f.id
                LEFT JOIN " . self::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " agab ON ag.id = agab.assessmentgroup_id
                LEFT JOIN " . AssessmentRepository::TABLE_NAME . " ab ON agab.assessmentblob_id = ab.id ";
        $params = [];

        if (!$showAllGroups) {
            $sql .= "
                WHERE (ag.company_id IS NULL OR ag.company_id = ?)
                AND ag.id NOT IN (
                    SELECT agp.assessmentgroup_id
                    FROM " . self::TABLE_GROUP_JOIN_TABLE . " agp
                    WHERE agp.company_id = ? AND agp.show = 0
                )";
            $params[] = $getCompanyId;
            $params[] = $getCompanyId;
        }
        $sql .= "ORDER BY ag.name ASC, agab.display_order ASC";
        return $this->getGroupsForSql($sql, $params);
    }

    /**
     * @param mixed $groupId
     * @return array<mixed>|null
     */
    public function getGroup($groupId)
    {
        $sql = "SELECT
                    ag.id, ag.name, ag.date_created, ag.date_updated, ab.name AS assessmentblob_name, ab.uid
                    , agab.assessmentblob_id, agab.assessmentgroup_id, agab.display_order
                    , f.id AS company_id, f.name AS company_name
                FROM " . self::TABLE_NAME . " ag
                LEFT JOIN facility f ON ag.company_id = f.id
                LEFT JOIN " . self::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " agab ON ag.id = agab.assessmentgroup_id
                LEFT JOIN " . AssessmentRepository::TABLE_NAME . " ab ON agab.assessmentblob_id = ab.id";
        $sql .= " WHERE ag.id = ? ";
        $params = [$groupId];
        $sql .= "ORDER BY ag.name ASC, agab.display_order ASC";
        $groups = $this->getGroupsForSql($sql, $params);
        if ($groups !== []) {
            return $groups[0];
        }
        return null;
    }

    /**
     * @param string $sql
     * @param array<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function getGroupsForSql($sql, $params)
    {
        // ag.id is the NOT-NULL primary key (used as an array key below); the
        // LEFT-JOINed columns are nullable, hence ?string. OpenEMR PDO returns
        // every column as string|null.
        /** @var list<array{id: string, name: ?string, date_created: ?string, date_updated: ?string, assessmentblob_name: ?string, uid: ?string, assessmentblob_id: ?string, assessmentgroup_id: ?string, display_order: ?string, company_id: ?string, company_name: ?string}> $results */
        $results = QueryUtils::fetchRecords($sql, $params);

        // we're going to group things
        $groupedResults = [];
        foreach ($results as $result) {
            if (!isset($groupedResults[$result['id']])) {
                $groupedResults[$result['id']] = [
                    'id' => $result['id'],
                    'name' => $result['name'],
                    'date_created' => $result['date_created'],
                    'date_updated' => $result['date_updated'],
                    'company' => null,
                    'assessmentGroupAssessmentBlobs' => []
                ];
                if (isset($result['company_id']) && $result['company_id'] !== '') {
                    $groupedResults[$result['id']]['company'] = [
                        'id' => $result['company_id'],
                        'name' => $result['company_name']
                    ];
                }
            }
            // we can have groups with no attached assessments
            if (isset($result['assessmentblob_id'])) {
                $groupedResults[$result['id']]['assessmentGroupAssessmentBlobs'][] = [
                    'assessmentBlob' => [
                        'id' => $result['assessmentblob_id']
                        , 'name' => $result['assessmentblob_name']
                        , 'uid' => $result['uid']
                    ]
                    , 'display_order' => $result['display_order']
                ];
            }
        }
        // TODO: @adunsulag need to sort by name and display_order

        $values = array_values($groupedResults);
        // sort $values by name using usort
        usort($values, function ($a, $b) {
            return $a['name'] <=> $b['name'];
        });
        return $values;
    }

    public function existsGroup(string $name, ?int $companyId): bool
    {
        $data = ['name' => $name];
        if ($companyId !== null && $companyId !== 0) {
            $data['company_id'] = $companyId;
        }
        $search = $this->search($data);
        if ($search->hasData()) {
            return true;
        } else {
            return false;
        }
    }

    public function createGroup(string $name, ?int $companyId): AssessmentGroup
    {
        $date = new \DateTime();
        $sql = "INSERT INTO " . self::TABLE_NAME . " (name,company_id, date_created) VALUES (?, ?,?)";
        $params = [$name, $companyId, $date->format("Y-m-d H:i:s.u")];
        $insertId = QueryUtils::sqlInsert($sql, $params);
        $group = new AssessmentGroup();
        $group->setId($insertId);
        $group->setName($name);
        $group->setCompanyId($companyId);
        $group->setCreated($date);
        $group->setUpdated($date);
        return $group;
    }

    /**
     * @param mixed $groupId
     * @return array<mixed>|null
     */
    public function addAssessmentToGroup(mixed $uid, $groupId, ?int $companyId)
    {
        $group = $this->getGroup($groupId);
        if ($group === null) {
            throw new \InvalidArgumentException("Group not found");
        }
        $sql = "INSERT INTO " . self::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " (assessmentblob_id,assessmentgroup_id) "
                . " SELECT max(ab.id),? FROM " . AssessmentRepository::TABLE_NAME . " ab WHERE ab.uid = ?";
        $params = [$groupId, $uid];

        QueryUtils::sqlStatementThrowException($sql, $params);
        return $this->getGroup($groupId);
    }

    /**
     * @param mixed $groupId
     * @return array<mixed>|null
     */
    public function updateAssessmentVersionForGroup($groupId)
    {
            // TODO: stephen need the creator/last updator columns on here to track changes...
            $sqlUpdate = "UPDATE " . self::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " agab_new
                    JOIN
                    (SELECT assessmentblob_id AS old_id, uid FROM " . self::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " agab
                     JOIN " . AssessmentRepository::TABLE_NAME . " ab ON agab.assessmentblob_id = ab.id
                     WHERE assessmentgroup_id = ?) old
                     ON old.old_id = agab_new.assessmentblob_id
                    JOIN
                    (SELECT max(id) AS new_id, uid FROM " . AssessmentRepository::TABLE_NAME . " ab2 GROUP BY uid) new
                    ON new.uid = old.uid
                    SET agab_new.assessmentblob_id = new.new_id
                    WHERE agab_new.assessmentgroup_id = ? AND agab_new.assessmentblob_id = old.old_id";
            $params = [$groupId, $groupId];
            QueryUtils::sqlStatementThrowException($sqlUpdate, $params);
            // return the updated group.
            $group = $this->getGroup($groupId);
            return $group;
    }
}
