<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Services\PatientService;

class TaskOnsitePortalActivityAccessService
{
    public function updateOnSitePortalActivityWithCompletion($auditRecordId)
    {
        $sql = "UPDATE onsite_portal_activity SET pending_action='completed',status='closed' WHERE id = ? ";
        $binds = [$auditRecordId];
        QueryUtils::sqlStatementThrowException($sql, $binds);
    }

    public function createOnSitePortalActivity($puuid, $activity, $narrative, $table_args, $action_user = '0')
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $record = [
            'id' => null
            ,'date' => $date
            ,'activity' => $activity
            ,'require_audit' => 1
            ,'pending_action' => 'review'
            ,'status' => 'waiting'
            ,'narrative' => $narrative
            ,'table_action' => 'update'
            ,'table_args' => $table_args
            ,'action_user' => $action_user
            ,'action_taken_time' => $date
            ,'checksum' => 0
            ,'puuid' => UuidRegistry::uuidToBytes($puuid)
        ];
        $columnRepeat = str_repeat("?,", count($record) - 1) . " (select pid FROM "
            . PatientService::TABLE_NAME . " WHERE uuid = ?)";
        $sql = 'INSERT INTO `onsite_portal_activity`(`id`, `date`, `activity`, `require_audit`
                ,`pending_action`,`status`,`narrative`,`table_action`,`table_args`,`action_user`, `action_taken_time`
                ,`checksum`, `patient_id`) VALUES (' . $columnRepeat . ') ';
        return QueryUtils::sqlInsert($sql, array_values($record));
    }
}
