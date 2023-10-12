<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\System\System;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedTemplateProfile;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use OpenEMR\Services\AppointmentService;
use OpenEMR\Services\BaseService;
use OpenEMR\Services\DocumentService;
use OpenEMR\Services\DocumentTemplates\DocumentTemplateService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use OpenEMR\Services\Search\FhirSearchWhereClauseBuilder;
use OpenEMR\Services\Search\TokenSearchField;

class AssignmentRepository
{
    const TABLE_NAME = "dac_Assignment";
    // TODO: @adunsulag there is a circular dependency between AssignmentRepository and AssessmentResultRepository... need to look at breaking that.
    const TABLE_NAME_ASSIGNMENT_ITEM = "dac_AssignmentItem";

    const TEMPLATE_PROFILE_LIST_ID = "Document_Template_Profiles";

    public function __construct()
    {
    }

    /**
     * @param Client[] $clients
     * @return Assignment[]
     */
    public function populateAssignmentsForClients(array $clients)
    {

        // grab our client ids
        $uuids = array_map(function ($client) {
            // make sure we don't have a SQL injection problem when we implode these by intval'ing them
            return $client->getId();
        }, $clients);
        // invert the array so we can look up the client by id
        $clientIndexesByUuid = array_flip($uuids);
        $search = [new TokenSearchField('client_uuid', $uuids, true)];
        $assignments = $this->search($search);
        foreach ($assignments as $assignment) {
            $client = $assignment->getClientId();
            if (isset($clientIndexesByUuid[$client])) {
                $clientIndex = $clientIndexesByUuid[$client];
                $clients[$clientIndex]->addAssignment($assignment);
            }
        }
    }

    public function getAssignmentByUuid($assignmentUuid): ?Assignment
    {
        $assignments = $this->search([new TokenSearchField('assignment_uuid', [$assignmentUuid], true)]);
        return $assignments[0] ?? null;
    }

    public function getAssignmentForAssignmentItemUuid($assignmentItemUuid): ?Assignment
    {
        $assignments = $this->search([new TokenSearchField('assignmentitem_uuid', [$assignmentItemUuid], true)]);
        return $assignments[0] ?? null;
    }

    /**
     * @param $search
     * @param $isAndCondition
     * @return Assignment[]
     */
    public function search($search, $isAndCondition = false)
    {
        $sqlAssignmentIds = "SELECT a.id";
        $sqlColumns = "SELECT
                a.id
                ,a.assignment_uuid
                , a.client_id
                , a.date_updated
                , a.assessmentgroup_id
                , a.date_updated
                , a.date_created
                , a.date_assigned
                , a.date_completed
                ,c.client_uuid
                ,ag.assessmentgroup_name
                ,ab.assessmentblob_name
                ,ab.assessmentblob_uid
                ,ab.assessmentblob_uuid
                ,ai.asset_id
                ,lab.asset_name
                ,lab.asset_uuid
                ,ai.assetresultblob_id
                ,ai.assignmentitem_uuid
                ,ai.assignmentitem_id
                ,ai.assessmentblob_id
                ,ai.assessmentresultblob_id
                ,ai.assignmentitem_date_assigned
                ,ai.assignmentitem_date_completed
                ,quest.questionnaire_name
                ,quest.questionnaire_uuid
                ,qr.questionnaire_response_id
                ,docs.document_name
                ,docs.document_uuid
                ,dtp.template_id
                ,dtp.template_name
                ,doc_profiles.profile_name
                ,doc_profiles.profile_id
                ,cal.calendar_event_uuid
                ,cal.pc_eid
                ,a.assignment_audit_id
                ,ai.assignmentitem_audit_id
                ,a_opa.opa_assignment_audit_status
                ,ai_opa.opa_assignmentitem_audit_status
        ";

        $from = " FROM (
                    SELECT id
                    ,uuid AS assignment_uuid
                    ,client_id
                    ,date_updated
                    ,assessmentgroup_id
                    ,date_created
                    ,date_assigned
                    ,date_completed
                    ,template_profile_list_option_id
                    ,appointment_id
                    ,audit_id AS assignment_audit_id
                    FROM " . self::TABLE_NAME . "
                 ) a
                JOIN (
                    SELECT
                        uuid AS client_uuid
                        , id AS client_id
                        , pid AS client_pid FROM " . PatientService::TABLE_NAME . "
            ) c ON c.client_pid = a.client_id
            LEFT JOIN (
                    SELECT
                        id AS assessmentgroup_id
                        ,name AS assessmentgroup_name
                    FROM
                        " . AssessmentGroupService::TABLE_NAME . "
             ) ag ON ag.assessmentgroup_id = a.assessmentgroup_id
            -- only grab the non-assessment group items so we can loop through and hydrate more efficiently
             LEFT JOIN (
                    SELECT
                        id AS assignmentitem_id
                        ,uuid AS assignmentitem_uuid
                        ,assessmentblob_id
                        ,assessmentresultblob_id
                        ,assetresultblob_id
                        ,asset_id
                        ,date_assigned AS assignmentitem_date_assigned
                        ,date_completed AS assignmentitem_date_completed
                        ,questionnaire_id
                        ,questionnaire_response_id
                        ,assignment_id
                        ,document_template_id
                        ,document_id
                        ,audit_id AS assignmentitem_audit_id
                    FROM " . self::TABLE_NAME_ASSIGNMENT_ITEM . "
             ) ai ON ai.assignment_id = a.id
             LEFT JOIN (
                    SELECT
                        id AS assessmentblob_id
                        ,name AS assessmentblob_name
                        ,uid AS assessmentblob_uid
                        ,uuid AS assessmentblob_uuid
                    FROM
                        " . AssessmentRepository::TABLE_NAME . "
                 ) ab ON ab.assessmentblob_id = ai.assessmentblob_id
             LEFT JOIN (
                        SELECT
                            id AS asset_id
                            ,uuid AS asset_uuid
                            ,title AS asset_name
                        FROM " . LibraryAssetBlobRepository::TABLE_NAME . "
                ) lab ON lab.asset_id = ai.asset_id
             LEFT JOIN (
                    SELECT
                        id AS assetresultblob_id
                    FROM
                    " . LibraryAssetResultBlobRepository::TABLE_NAME . "
                ) larb ON larb.assetresultblob_id = ai.assetresultblob_id
             LEFT JOIN (
                    SELECT
                        id AS questionnaire_id
                        ,name AS questionnaire_name
                        ,questionnaire_id AS questionnaire_uuid
                    FROM " . QuestionnaireService::TABLE_NAME . "
             ) quest ON quest.questionnaire_id = ai.questionnaire_id
             LEFT JOIN (
                    SELECT
                        response_id AS questionnaire_response_id
                    FROM " . QuestionnaireResponseService::TABLE_NAME . "
                ) qr ON qr.questionnaire_response_id = ai.questionnaire_response_id
            -- now let's add in the profile types of assignments
            LEFT JOIN (
                    SELECT
                        option_id AS profile_id
                        ,list_id AS profile_list_id
                        ,title AS profile_name
                    FROM
                        list_options
                    WHERE
                        list_id = 'Document_Template_Profiles'
                ) doc_profiles ON doc_profiles.profile_id = a.template_profile_list_option_id AND doc_profiles.profile_list_id='Document_Template_Profiles'
            -- now we will bring in the individual assignment items
            LEFT JOIN (
                SELECT
                    id AS template_id
                    ,template_name
                FROM
                    document_templates
                ) dtp ON dtp.template_id = ai.document_template_id
             LEFT JOIN (
                    SELECT
                        id AS doc_id
                        ,uuid AS document_uuid
                        ,name AS document_name
                FROM
                    " . DocumentService::TABLE_NAME . "
                ) docs ON docs.doc_id = ai.document_id
            LEFT JOIN (
                    SELECT
                        pc_eid
                        ,uuid AS calendar_event_uuid
                    FROM
                        " . AppointmentService::TABLE_NAME . "
                ) cal ON cal.pc_eid = a.appointment_id
             LEFT JOIN (
                    SELECT
                        id AS opa_assignment_audit_id
                        ,status AS opa_assignment_audit_status
                    FROM
                        onsite_portal_activity
                )  a_opa ON a_opa.opa_assignment_audit_id = a.assignment_audit_id
             LEFT JOIN (
                    SELECT
                        id AS opa_assignmentitem_audit_id
                        ,status AS opa_assignmentitem_audit_status
                    FROM
                        onsite_portal_activity
                )  ai_opa ON ai_opa.opa_assignmentitem_audit_id = ai.assignmentitem_audit_id
            ";

        $whereClause = FhirSearchWhereClauseBuilder::build($search, $isAndCondition);
        $sqlIds = $sqlAssignmentIds . $from . $whereClause->getFragment();
        $ids = QueryUtils::fetchTableColumn($sqlIds, 'id', $whereClause->getBoundValues());
        $recordsById = [];
        $assignments = [];
        if (!empty($ids)) {
            $boundIdString = rtrim(str_repeat("?,", count($ids) - 1)) . "?";
            $sql = $sqlColumns . $from . " WHERE a.id IN (" . $boundIdString . ")";
            $records = QueryUtils::fetchRecords($sql, $ids);

            foreach ($records as $record) {
                $recordId = $record['id'];
                if (empty($recordsById[$recordId])) {
                    $recordsById[$recordId] = $record;
                    $recordsById[$recordId]['items'] = [];
                }
                if (isset($record['assignmentitem_id'])) {
                    $recordsById[$recordId]['items'][] = $this->getItemArrayFromAssignmentItemRecord($record);
                }
            }

            foreach ($recordsById as $recordId => $record) {
                $assignments[] = $this->hydrateAssignmentFromRecord($record);
            }
        }
        return $assignments;
    }

    private function hydrateAssignmentFromRecord(array $record): Assignment
    {
        if (isset($record['assessmentgroup_id'])) {
            $assignment = new AssignedAssessmentGroup();
            $this->hydrateAssignedAssessmentGroupFromRecord($record, $assignment);
        } else if (isset($record['profile_id'])) {
            $assignment = new AssignedTemplateProfile();
            $this->hydrateAssignedTemplateProfileFromRecord($record, $assignment);
        } else {
            $assignment = new Assignment();
            foreach ($record['items'] as $item) {
                $assignmentItem = $this->hydrateItemFromRecord($item);
                $assignment->addItem($assignmentItem);
            }
        }

        if (isset($record['calendar_event_uuid'])) {
            $assignment->setAppointmentId(UuidRegistry::uuidToString($record['calendar_event_uuid']));
        }

        if (isset($record['client_uuid'])) {
            $assignment->setClientId(UuidRegistry::uuidToString($record['client_uuid']));
        }

        if (!empty($record['assignment_audit_id'])) {
            $assignment->setAuditId($record['assignment_audit_id']);
        }
        $assignment->setId(UuidRegistry::uuidToString($record['assignment_uuid']));
        if (count($assignment->getItems()) == 1 && !$assignment->isGroupType()) {
            $item = $assignment->getItems()[0];
            // use the sub item to populate the current assignment.
            $assignment->setType($item->getType());
            $assignment->setName($item->getName());
        }
        // for now let's just return an empty object.
        $this->populateDatesForAssignment($record, $assignment);
        return $assignment;
    }

    private function populateDatesForAssignment($record, Assignment $assignment)
    {
        $dateFormat = "Y-m-d H:i:s.u";
        if (!empty($record['date_assigned'])) {
            $assignment->setDateAssigned(\DateTime::createFromFormat($dateFormat, $record['date_assigned']));
        }
        if (!empty($record['date_completed'])) {
            $assignment->setDateCompleted(\DateTime::createFromFormat($dateFormat, $record['date_completed']));
        }
    }

    private function hydrateAssignedAssessmentFromRecord(array $record, AssignedAssessment $assessment)
    {
        $assessment->setItemId($record['id']);
        $assessment->setAssessmentId($record['assessmentblob_id']);
        if (!empty($record['assessmentblob_uuid'])) {
            $uuid = $record['assessmentblob_uuid'];
        } else {
            // we lazy generate our uuid if we need it so we can move forward
            $uuid = AssessmentRepository::updateAssessmentUuid($record['assessmentblob_id']);
        }
        // now we are populating the uuid.
        $assessment->setAssessmentUuid(UuidRegistry::uuidToString($uuid));
        $assessment->setName($record['assessmentblob_name']);
        $assessment->setUid($record['assessmentblob_uid']);
        if (!empty($record['assessmentresultblob_id'])) {
            $assessment->setResultId($record['assessmentresultblob_id']);
        }
    }
    private function hydrateAssignedAssessmentGroupFromRecord(array $record, AssignedAssessmentGroup $assessmentGroup)
    {
        $assessmentGroup->setAssessmentGroupId($record['assessmentgroup_id']);
        $assessmentGroup->setName($record['assessmentgroup_name']);
        if (!empty($record['items'])) {
            foreach ($record['items'] as $item) {
                $assignmentItem = $this->hydrateItemFromRecord($item);
                $assessmentGroup->addItem($assignmentItem);
            }
        }
    }

    private function hydrateAssignedTemplateProfileFromRecord(array $record, AssignedTemplateProfile $profile)
    {
        $profile->setProfileId($record['profile_id']);
        $profile->setName($record['profile_name']);
        if (!empty($record['items'])) {
            foreach ($record['items'] as $item) {
                // note the only items hydrated here are where the document template is a questionnaire category.
                $assignmentItem = $this->hydrateItemFromRecord($item);
                $profile->addItem($assignmentItem);
            }
        }
    }

    private function hydrateItemFromRecord($item): Assignment
    {
        if (isset($item['assessmentblob_id'])) {
            $assignmentItem = new AssignedAssessment();
            $this->hydrateAssignedAssessmentFromRecord($item, $assignmentItem);
        } else if (isset($item['asset_id'])) {
            $assignmentItem = new AssignedLibraryAsset();
            $this->hydrateAssignedLibraryAssetFromRecord($item, $assignmentItem);
        } else if (isset($item['template_id'])) {
            $assignmentItem = new AssignedQuestionnaire();
            $this->hydrateDocumentTemplateProfile($item, $assignmentItem);
        } else if (isset($item['questionnaire_uuid'])) {
            $assignmentItem = new AssignedQuestionnaire();
            $this->hydrateAssignedQuestionnaireFromRecord($item, $assignmentItem);
        } else {
            throw new \Exception("Unknown assignment item type");
        }
        if (!empty($item['audit_id'])) {
            $assignmentItem->setAuditId($item['audit_id']);
        }
        if (!empty($item['client_uuid'])) {
            $assignmentItem->setClientId(UuidRegistry::uuidToString($item['client_uuid']));
        }
        $assignmentItem->setId(UuidRegistry::uuidToString($item['uuid']));
        $this->populateDatesForAssignment($item, $assignmentItem);
        return $assignmentItem;
    }

    private function hydrateAssignedLibraryAssetFromRecord(array $item, AssignedLibraryAsset $asset)
    {
        $this->populateDatesForAssignment($item, $asset);
        $asset->setAssetId($item['asset_id']);
        if (empty($item['asset_uuid'])) {
            $uuid = LibraryAssetBlobRepository::updateLibraryAssetBlobUuid($item['asset_id']);
        } else {
            $uuid = $item['asset_uuid'];
        }

        $asset->setAssetUuid(UuidRegistry::uuidToString($uuid));
        $asset->setName($item['asset_name']);
        $asset->setResultId($item['assetresultblob_id']);
    }
    private function createAssignmentItemFromObject($assignmentId, Assignment $item)
    {
        $uuid = (new UuidRegistry(['table_name' => self::TABLE_NAME_ASSIGNMENT_ITEM]))->createUuid();
        if ($item instanceof AssignedAssessment) {
            $sqlItem = "INSERT INTO " . self::TABLE_NAME_ASSIGNMENT_ITEM . " (uuid, assignment_id, assessmentblob_id, date_assigned) VALUES (?, ?, ?, ?)";
            $itemParams = [$uuid, $assignmentId, $item->getAssessmentId(), $item->getDateAssigned()->format("Y-m-d H:i:s.u")];
        } else if ($item instanceof AssignedLibraryAsset) {
            $sqlItem = "INSERT INTO " . self::TABLE_NAME_ASSIGNMENT_ITEM . " (uuid, assignment_id, asset_id, date_assigned) VALUES (?, ?, ?, ?)";
            $itemParams = [$uuid, $assignmentId, $item->getAssetId(), $item->getDateAssigned()->format("Y-m-d H:i:s.u")];
        } else if ($item instanceof AssignedQuestionnaire) {
            $sqlItem = "INSERT INTO " . self::TABLE_NAME_ASSIGNMENT_ITEM . "(uuid, assignment_id, questionnaire_id, document_template_id, "
                . " date_assigned) VALUES (?, ?, (SELECT id FROM " . QuestionnaireService::TABLE_NAME . " WHERE questionnaire_id = ?), ?, ?)";
            $itemParams = [$uuid, $assignmentId, $item->getQuestionnaireId(), $item->getDocumentTemplateId(),
                $item->getDateAssigned()->format("Y-m-d H:i:s.u")];
        }
        QueryUtils::sqlInsert($sqlItem, $itemParams);
        $item->setId(UuidRegistry::uuidToString($uuid));
        return $item;
    }

    public function removeAssignment($clientId, $assignmentId, int $userId)
    {
        $uuidBytes = UuidRegistry::uuidToBytes($assignmentId);
        $clientBytes = UuidRegistry::uuidToBytes($clientId);
        $sqlItem = "DELETE FROM " . self::TABLE_NAME_ASSIGNMENT_ITEM . " WHERE assignment_id IN (SELECT id FROM " .
            self::TABLE_NAME . " WHERE uuid = ? AND client_id = (SELECT pid FROM " . PatientService::TABLE_NAME . " WHERE uuid = ?))";
        QueryUtils::sqlStatementThrowException($sqlItem, [$uuidBytes, $clientBytes]);

        $sql = "DELETE FROM " . self::TABLE_NAME . " WHERE uuid = ? AND client_id = (SELECT pid FROM " . PatientService::TABLE_NAME . " WHERE uuid = ?)";
        QueryUtils::sqlStatementThrowException($sql, [$uuidBytes, $clientBytes]);
        return $assignmentId;
    }

    public function saveAssignmentForClient($clientId, Assignment $assignment, int $userId)
    {
        $items = $assignment->getItems();
        if (empty($items)) {
            throw new \InvalidArgumentException("No items to save for assignment");
        }
        $item = $items[0];
        $sql = "INSERT INTO " . self::TABLE_NAME . " (uuid, client_id, date_assigned, appointment_id,  audit_id"
        . ", template_profile_list_option_id, assessmentgroup_id) VALUES (?, (SELECT pid FROM "
            . PatientService::TABLE_NAME . " WHERE uuid = ?), ?, "
            // we need to make sure we don't allow someone to set an appointment for someone other than the current patient
            . " (select pc_eid FROM " . AppointmentService::TABLE_NAME . " WHERE uuid = ? AND pc_pid IN (select pid FROM "
            . PatientService::TABLE_NAME . " WHERE uuid = ?)), ?, ?, ?)";
        $assignmentUuid = (new UuidRegistry(['table_name' => self::TABLE_NAME]))->createUuid();

        $clientUuid = UuidRegistry::uuidToBytes($clientId);
        $appointmentId = !empty($assignment->getAppointmentId()) ? UuidRegistry::uuidToBytes($assignment->getAppointmentId()) : null;
        $params = [$assignmentUuid, $clientUuid
            , $assignment->getDateAssigned()->format("Y-m-d H:i:s.u")
            , $appointmentId, $clientUuid, $assignment->getAuditId()];
        if ($assignment instanceof AssignedTemplateProfile) {
            $params[] = $assignment->getProfileId();
            $params[] = null;
        } else if ($assignment instanceof AssignedAssessmentGroup) {
            $params[] = null;
            $params[] = $assignment->getAssessmentGroupId();
        } else {
            $params[] = null;
            $params[] = null;
        }
        QueryUtils::sqlInsert($sql, $params);
        $assignmentId = QueryUtils::getLastInsertId();
        $assignment->setId(UuidRegistry::uuidToString($assignmentUuid));
        $items = $assignment->getItems() ?? [];
        $createdItems = [];
        foreach ($items as $item) {
            $createdItems[] = $this->createAssignmentItemFromObject($assignmentId, $item);
        }
        $assignment->setItems($createdItems);
        return $assignment;
    }

    public function getAssignmentItem(string $assignmentItemUuid, string $puuid)
    {
        // tokens have to be strings... not sure why we force that.
        $search = [new TokenSearchField('assignmentitem_uuid', $assignmentItemUuid, true)];
        $search[] = new TokenSearchField('client_uuid', $puuid, true);
        $assignments = $this->search($search, true);
        if (!empty($assignments)) {
            $assignment = $assignments[0];
            return $assignment->getItemForId($assignmentItemUuid);
        }
        return null;
    }

    public function updateCompletedAssignmentItem(Assignment $item)
    {
        $resultId = null;
        $item->setDateCompleted(new \DateTime());
        $params = [$item->getDateCompleted()->format("Y-m-d H:i:s.u"), $resultId];
        $auditId = $this->createAuditRecordForItem($item);
        $item->setAuditId($auditId);
        (new SystemLogger())->debug("Updating assignment item with audit id ", ['auditId' => $auditId]);
        $params[] = $auditId;
        if ($item instanceof AssignedAssessment) {
            $sql = "UPDATE " . self::TABLE_NAME_ASSIGNMENT_ITEM . " SET date_completed = ?, assessmentresultblob_id = ?, audit_id = ? WHERE uuid = ?";
            $params[1] = $item->getResultId();
        } else if ($item instanceof AssignedLibraryAsset) {
            $sql = "UPDATE " . self::TABLE_NAME_ASSIGNMENT_ITEM . " SET date_completed = ?, assetresultblob_id = ?, audit_id = ? WHERE uuid = ?";
            $params[1] = $item->getResultId();
        } else if ($item instanceof AssignedQuestionnaire) {
            $sql = "UPDATE " . self::TABLE_NAME_ASSIGNMENT_ITEM . " SET date_completed = ?, questionnaire_response_id=?,audit_id=?,document_template_id=?,document_id=(select id FROM "
                . DocumentService::TABLE_NAME . " WHERE uuid = ?) WHERE uuid = ?";
            $params[1] = $item->getResultId();
            $params[] = $item->getDocumentTemplateId();
            if (!empty($item->getDocumentId())) {
                $params[] = UuidRegistry::uuidToBytes($item->getDocumentId());
            } else {
                $params[] = null;
            }
        }
        (new SystemLogger())->debug("Running sql pre uuid", ['sql' => $sql, 'params' => $params]);
        $params[] = UuidRegistry::uuidToBytes($item->getId());
        QueryUtils::sqlStatementThrowException($sql, $params);

        $assignmentId = $this->getAssignmentIdForAssignmentItem($item);
        if ($this->hasCompletedAssignmentItems($assignmentId)) {
            $this->updateCompletedAssignment($assignmentId, $item->getDateCompleted());
            // TODO: @adunsulag look at adding an audit record for the entire group category if there is one.
        }
        return $item;
    }

    public function getAssignmentIdForAssignmentItem(Assignment $item)
    {
        return QueryUtils::fetchSingleValue(
            "SELECT assignment_id FROM " . self::TABLE_NAME_ASSIGNMENT_ITEM . " WHERE uuid = ?",
            'assignment_id',
            [UuidRegistry::uuidToBytes($item->getId())]
        );
    }

    public function hasCompletedAssignmentItems(int $assignmentId)
    {
        $sql = "SELECT COUNT(item.id) AS count FROM " . self::TABLE_NAME_ASSIGNMENT_ITEM . " item WHERE item.assignment_id = ? AND item.date_completed IS NULL";
        $count = QueryUtils::fetchSingleValue($sql, 'count', [$assignmentId]);
        return $count == 0;
    }
    public function hasCompletedAssignments(int $clientId)
    {
        $sql = "SELECT COUNT(id) AS count FROM " . self::TABLE_NAME . " WHERE client_id = ? AND date_completed IS NULL";
        $count = QueryUtils::fetchSingleValue($sql, 'count', [$clientId]);
        return $count == 0;
    }

    private function updateCompletedAssignment(int $assignmentId, \DateTime $dateCompleted)
    {
        $sql = "UPDATE " . self::TABLE_NAME . " SET date_completed = ?,date_updated=? WHERE id = ?";
        $dateCompletedString = $dateCompleted->format("Y-m-d H:i:s.u");
        QueryUtils::sqlStatementThrowException($sql, [$dateCompletedString,$dateCompletedString, $assignmentId]);
    }

    private function hydrateDocumentTemplateProfile($item, AssignedQuestionnaire $assignmentItem)
    {
        $assignmentItem->setDocumentTemplateId($item['template_id']);
        // if we have a document result we can put that here.
        if (!empty($item['document_uuid'])) {
            $assignmentItem->setDocumentId(UuidRegistry::uuidToString($item['document_uuid']));
        }
        if (!empty($item['questionnaire_uuid'])) {
            $this->hydrateAssignedQuestionnaireFromRecord($item, $assignmentItem);
        } else {
            (new SystemLogger())->errorLogCaller("No questionnaire uuid for document template profile assignment item, data integrity error", ['id' => $item['id']]);
        }
    }

    private function hydrateAssignedQuestionnaireFromRecord($item, AssignedQuestionnaire $assignmentItem)
    {
        $this->populateDatesForAssignment($item, $assignmentItem);
        $assignmentItem->setQuestionnaireId($item['questionnaire_uuid']);
        $assignmentItem->setName($item['questionnaire_name']);
        $assignmentItem->setResultId($item['questionnaire_response_id']);
    }

    /**
     * @param int $pid
     * @param string $questionnaireId
     * @return Assignment[]
     * @throws \Exception
     */
    public function getQuestionnaireAssignmentItemsForClient(int $pid, string $questionnaireId)
    {
        $search = [new TokenSearchField('client_pid', (string)$pid, false)];
        // its a native string value which is why we don't treat it as a binary uuid
        $search[] = new TokenSearchField('questionnaire_uuid', $questionnaireId, false);
        $assignments = $this->search($search, true);
        $assignmentItems = [];
        foreach ($assignments as $assignment) {
            foreach ($assignment->getItems() as $item) {
                if ($item instanceof AssignedQuestionnaire) {
                    $assignmentItems[] = $item;
                }
            }
        }
        return $assignmentItems;
    }

    private function getItemArrayFromAssignmentItemRecord(array $record)
    {
        $item = [
            'id' => $record['assignmentitem_id'],
            'uuid' => $record['assignmentitem_uuid'],
            'date_assigned' => $record['assignmentitem_date_assigned'],
            'date_completed' => $record['assignmentitem_date_completed'],
            'assessmentblob_id' => $record['assessmentblob_id'],
            'assessmentblob_name' => $record['assessmentblob_name'],
            'assessmentblob_uuid' => $record['assessmentblob_uuid'],
            'asset_name' => $record['asset_name'],
            'asset_id' => $record['asset_id'],
            'asset_uuid' => $record['asset_uuid'],
            'assetresultblob_id' => $record['assetresultblob_id'],
            'assessmentblob_uid' => $record['assessmentblob_uid'],
            'assessmentresultblob_id' => $record['assessmentresultblob_id'],
            'questionnaire_name' => $record['questionnaire_name'],
            'questionnaire_uuid' => $record['questionnaire_uuid'],
            'questionnaire_response_id' => $record['questionnaire_response_id'],
            'document_name' => $record['document_name'],
            'document_uuid' => $record['document_uuid'],
            'template_id' => $record['template_id'],
            'template_name' => $record['template_name'],
            'audit_id' => $record['assignmentitem_audit_id'],
            'client_uuid' => $record['client_uuid']
        ];
        return $item;
    }

    public function getAssignmentUuidsForAppointment(?int $pc_eid)
    {
        if (empty($pc_eid)) {
            return null;
        }
        $sql = "SELECT uuid FROM " . self::TABLE_NAME . " WHERE appointment_id = ?";
        $assignmentUuids = QueryUtils::fetchTableColumn($sql, 'uuid', [$pc_eid]);
        if (!empty($assignmentUuids)) {
            return array_map(function ($val) {
                return UuidRegistry::uuidToString($val);
            }, $assignmentUuids);
        }
        return null;
    }

    public function getAssignmentsForAppointmentId(?int $pc_eid): ?array
    {
        if (empty($pc_eid)) {
            return null;
        }
        return $this->search([new TokenSearchField('appointment_id', $pc_eid, false)], true);
    }

    public function getTemplateProfileAssignmentForAppointmentId(?int $pc_eid): ?AssignedTemplateProfile
    {
        $assignments = $this->getAssignmentsForAppointmentId($pc_eid) ?? [];
        foreach ($assignments as $assignment) {
            if ($assignment instanceof AssignedTemplateProfile) {
                return $assignment;
            }
        }
        return null;
    }

    public function createClientAssignmentForProfile($clientId, $appointmentId, $profileName, $profileId, $userId): Assignment
    {
        $questionnaireService = new QuestionnaireService();
        $dateAssigned = new \DateTime();
        $templateProfile = new AssignedTemplateProfile();
        $templateProfile->setProfileId($profileId);
        $templateProfile->setName($profileName);
        $templateProfile->setAppointmentId($appointmentId);
        $templateProfile->setClientId($clientId);
        $templateProfile->setDateAssigned($dateAssigned);

        // now we need
        $docService = new DocumentTemplateService();
        $templateList = $docService->getTemplateListByProfile($templateProfile->getProfileId());
        // we only want to grab Questionnaire categories
        foreach ($templateList as $category => $templates) {
            // TODO: @adunsulag if we start supporting some of the other template documents we can address that here.
            // very odd that one category uses a capital Q and the other doesn't
            if ($category === "questionnaire" || $category == "Questionnaires") {
                foreach ($templates as $template) {
                    // need to extract the questionnaire id from the template content
                    $questionnaire = $template['template_content'];
                    $id = null;
                    if (preg_match('/{Questionnaire:\s*(\d+)}/', $questionnaire, $matches)) {
                        $id = $matches[1];
                    } else {
                        // invalid content so just continue
                        continue;
                    }
                    $item = new AssignedQuestionnaire();
                    $uuid = BaseService::getUuidById($id, QuestionnaireService::TABLE_NAME, 'id');
                    if (empty($uuid)) {
                        continue; // invalid questionnaire id so we can't continue
                    }
                    $item->setQuestionnaireId(UuidRegistry::uuidToString($uuid));
                    $item->setDocumentTemplateId($template['id']);
                    $item->setDateAssigned($dateAssigned);
                    $item->setName($template['template_name']);
                    $templateProfile->addItem($item);
                }
            }
        }
        return $this->saveAssignmentForClient($clientId, $templateProfile, $userId);
    }

    /**
     * @param string|int $recordId
     * @return Assignment[]
     */
    public function getAssignmentItemsForAuditId(string|int $recordId): array
    {
        $search = [new TokenSearchField('assignmentitem_audit_id', (string)$recordId, false)];
        $assignments = $this->search($search, true);
        $assignmentItems = [];
        foreach ($assignments as $assignment) {
            foreach ($assignment->getItems() as $item) {
                if ($item->getAuditId() == $recordId) {
                    $assignmentItems[] = $item;
                }
            }
        }
        return $assignmentItems;
    }

    private function createAuditRecordForItem(Assignment $item)
    {
//        if ($item instanceof AssignedQuestionnaire) {
//            // TODO: @adunsulag if we can consolidate this code that would be wonderful
//            return null;
//        }
        $onsiteService = new TaskOnsitePortalActivityAccessService();
        $portalAuditId = $onsiteService->createOnSitePortalActivity(
            $item->getClientId(),
            'dc-assignment',
            $item->getName(),
            ''
        ); // no table args as the fk connector is in the Assignment table.
        return $portalAuditId;
    }

    public function getAssignmentsForEncounterUuid(string $encounterUuid, string $puuid)
    {
        // for now just return the assignment list
        $search = [new TokenSearchField('client_uuid', [$puuid], true)];
        $assignments = $this->search($search);
        return $assignments;
    }


    public function getQuestionnaireAssignmentItemsForEncounter(string $encounterUuid, string $questionnaireId)
    {
        return [];
    }

    public function unlinkAssignmentFromAppointment(string $existingAssignmentId, string $event_uuid, int $authUserID)
    {
        $sql = "UPDATE " . self::TABLE_NAME . " SET appointment_id = NULL, date_updated=NOW(), "
        . " last_updated_by=? WHERE uuid = ? "
        . " AND appointment_id = (select pc_eid FROM " . AppointmentService::TABLE_NAME . " WHERE uuid = ?) ";
        $uuidBytes = UuidRegistry::uuidToBytes($existingAssignmentId);
        $appointmentUuid = UuidRegistry::uuidToBytes($event_uuid);
        QueryUtils::sqlStatementThrowException($sql, [$authUserID, $uuidBytes, $appointmentUuid]);
        return $existingAssignmentId;
    }
}
