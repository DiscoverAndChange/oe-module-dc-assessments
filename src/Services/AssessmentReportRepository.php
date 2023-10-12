<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentGroup;

class AssessmentReportRepository
{
    const TABLE_NAME = "dac_Report";

    const TABLE_VERSION_NAME = "dac_ReportVersion";

    const TABLE_PERMISSION_NAME = "dac_ReportPermission";

    public function getAll($showAllReports)
    {

        $sql = "select rv.*,vca.name AS assessment_name, ag.name AS assessmentgroup_name , ag.id AS assessmentgroup_id
                FROM " . self::TABLE_VERSION_NAME . " rv
                JOIN " . self::TABLE_NAME . " r ON rv.report_id = r.id
                JOIN (
                    SELECT report_id, max(version) AS version FROM " . self::TABLE_VERSION_NAME . " GROUP BY report_id
                 ) max_version ON rv.report_id = max_version.report_id AND rv.version = max_version.version
                JOIN " . self::TABLE_PERMISSION_NAME . " rp ON rp.report_id = r.id
                LEFT JOIN " . AssessmentGroupService::TABLE_NAME . " ag ON r.assessmentgroup_id = ag.id
                LEFT JOIN " . AssessmentRepository::TABLE_VIEW_CURRENT_ASSESSMENT . " vca ON r.assessment_uid = vca.uid
                WHERE 1=1 ";

        if (!$showAllReports) {
            $sql .= "AND rp.show = 1 ";
        }

        $sql .= "ORDER BY r.name";
        $results = QueryUtils::fetchRecords($sql, []);

        $hydratedResults = [];
        foreach ($results as $r) {
            $report = [];
            try {
                $report = json_decode($r['data'], true);
            } catch (Exception $e) {
                $this->logger->error($e);
            }
            $group = new AssessmentGroup();
            $group->setId($r['assessmentgroup_id'] ?? 0);
            $group->setName($r['assessmentgroup_name'] ?? '');
            if ($r['assessmentgroup_id']) {
                $report['linkedGroup'] = $group;
            }
            if ($r['assessment_uid']) {
                $report['linkedAssessments'] = [$r['assessment_uid']];
            }
            $hydratedResults[] = $report;
        }
        return $hydratedResults;
    }

    public function createReport(string $id, string $name, int $userId, $data, ?int $groupId, ?string $assessmentUid)
    {
        if (!empty($data['token'])) {
            unset($data['token']); // we do not want this as a carryover from old data.
        }
        $dataJSON = json_encode($data);
        $date = (new \DateTime())->format("Y-m-d H:i:s.u");
        $sql = "INSERT INTO " . self::TABLE_NAME . " (id, name, assessmentgroup_id, assessment_uid, created_by, creation_date, "
        . " last_updated_by, last_update_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $name, $groupId, $assessmentUid, $userId, $date, $userId,$date];
        QueryUtils::sqlStatementThrowException($sql, $params);

        $sqlReportVersion = "INSERT INTO " . self::TABLE_VERSION_NAME . " (report_id, data, created_by, creation_date, "
            . " last_updated_by, last_update_date)
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$id, $dataJSON, $userId, $date, $userId,$date];
        $versionId = QueryUtils::sqlInsert($sqlReportVersion, $params);
        // TODO: @adunsulag if we want to return the data we would do that here.

        // now we need to update the report permissions
        $insertSql = "INSERT INTO " . self::TABLE_PERMISSION_NAME . "(report_id, `show`, created_by, creation_date, last_updated_by, last_update_date ) "
            . " SELECT r.id as report_id, 1 as `show`, ? as created_by, NOW() as creation_date, ? as last_updated_by"
            . " , NOW() AS last_update_date FROM " . self::TABLE_NAME . " r LEFT JOIN " . self::TABLE_PERMISSION_NAME . " rp ON (r.id = rp.report_id AND company_id IS NULL )"
            . " WHERE r.id = ? AND rp.id IS NULL ";
        $params = [$userId, $userId, $id];
        QueryUtils::sqlStatementThrowException($insertSql, $params);
        // TODO: @adunsulag if we want to handle permissions by company we would handle this.
    }

    public function getOne($id)
    {
        $sql = "SELECT rv.data, r.assessmentgroup_id,ag.name AS assessmentgroup_name,r.assessment_uid, r.assessment_uid"
        . " , rp.id AS reportpermission_id, rp.show"
            . " FROM " . self::TABLE_VERSION_NAME . " rv JOIN " . self::TABLE_NAME . " r ON rv.report_id = r.id "
                . " LEFT JOIN " . AssessmentGroupService::TABLE_NAME . " ag ON r.assessmentgroup_id = ag.id "
            . " LEFT JOIN " . self::TABLE_PERMISSION_NAME . " rp ON rp.report_id = r.id AND rp.company_id IS NULL "
            . " WHERE r.id = ? and rv.version IN (SELECT max(rv2.version) FROM " . self::TABLE_VERSION_NAME . " rv2 WHERE rv2.report_id = ?) "
            . " ORDER BY rv.creation_date DESC LIMIT 1";
        $params = [$id, $id];
        $reports = QueryUtils::fetchRecords($sql, $params);
        if (!empty($reports)) {
            $report = $reports[0];
            $data = json_decode($report['data'], true);

            if (!empty($report['assessmentgroup_id'])) {
                $group = new AssessmentGroup();
                $group->setId($report['assessmentgroup_id'] ?? 0);
                $group->setName($report['assessmentgroup_name'] ?? '');
                $data['linkedGroup'] = $group;
            }

            if (!empty($report['assessment_uid'])) {
                $data['linkedAssessments'] = [$report['assessment_uid']];
            }
            return $data;
        }
    }

    public function updateReport(string $id, string $name, int $userId, array $data, ?int $assessmentGroupID, ?string $assessmentUid)
    {
        if (!empty($data['token'])) {
            unset($data['token']); // we do not want this as a carryover from old data.
        }
        $report = $this->getOne($id);
        if (empty($report)) {
            throw new \InvalidArgumentException("Report not found");
        }

        $date = (new \DateTime())->format("Y-m-d H:i:s.u");
        $updateSql = "UPDATE " . self::TABLE_NAME . " SET name = ? , assessmentgroup_id = ? , assessment_uid = ? , last_updated_by = ? , last_update_date = ? WHERE id = ?";
        $params = [$name, $assessmentGroupID, $assessmentUid, $userId, $date, $id];
        QueryUtils::sqlStatementThrowException($updateSql, $params);

        $sqlReportVersion = "INSERT INTO " . self::TABLE_VERSION_NAME . " (report_id, data, version, created_by, creation_date, "
            . " last_updated_by, last_update_date)
                VALUES (?, ?, (
                    select coalesce(max(version), 0)+1 as version FROM " . self::TABLE_VERSION_NAME . " rv2 WHERE report_id = (select id from " . self::TABLE_NAME . " where id = ?)
                ), ?, ?, ?, ?)";
        $dataJSON = json_encode($data);
        $params = [$id, $dataJSON, $id, $userId, $date, $userId,$date];
        return QueryUtils::sqlInsert($sqlReportVersion, $params);
    }

    public function existsReport(string $id)
    {
        $sql = "SELECT count(*) as count FROM " . self::TABLE_NAME . " WHERE id = ?";
        $params = [$id];
        $result = QueryUtils::fetchSingleValue($sql, 'count', $params);
        if (!empty($result)) {
            return $result > 0;
        }
        return false;
    }
}
