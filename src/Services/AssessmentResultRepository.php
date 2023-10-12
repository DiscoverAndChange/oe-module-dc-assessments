<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\FhirSearchWhereClauseBuilder;
use OpenEMR\Validators\ProcessingResult;

class AssessmentResultRepository
{
    const TABLE_NAME = "dac_AssessmentResultBlob";

    public function search($searchParams)
    {
        $processingResult = new ProcessingResult();

        $distinctIds = "SELECT DISTINCT arb.id ";
        $fromClause = " FROM " . self::TABLE_NAME . " arb "
            . " JOIN (SELECT data AS assessment_data, id AS assessment_id FROM " . AssessmentRepository::TABLE_NAME . ") ab ON (ab.assessment_id = arb.assessment_id) "
            . " JOIN ( SELECT pid AS client_id FROM " . PatientService::TABLE_NAME . ") c ON (arb.client_id = c.client_id) "
            . " LEFT JOIN (SELECT id AS assignmentitem_id, assessmentresultblob_id FROM " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . ") ai ON (assessmentresultblob_id = arb.id) ";

        $sql = " ORDER BY arb.date DESC ";
        $where = FhirSearchWhereClauseBuilder::build($searchParams);
        $query = $distinctIds . $fromClause . $where->getFragment() . $sql;

        $ids = QueryUtils::fetchTableColumn($query, 'id', $where->getBoundValues());
        if (empty($ids)) {
            return $processingResult;
        }

        $sql = "SELECT "
            . " arb.id, arb.assessment_id, arb.date, arb.data as 'result_data' "
            . " ,ab.assessment_data "
            . " ,ai.assignmentitem_id "
            . ",c.client_id ";
        $sql .= $fromClause;
        $bindClause = str_repeat("?,", count($ids) - 1) . "?";
        $sql .= "WHERE arb.id IN (" . $bindClause . ")";

        $records = QueryUtils::fetchRecords($sql, $ids);
        $results = $this->hydrateRecordsFromResult($records);
        foreach ($results as $blob) {
            $processingResult->addData($blob);
        }
        return $processingResult;
    }

    public function getResultListForPatient(string $clientId, array $resultIds)
    {
        if (empty($resultIds)) {
            throw new \InvalidArgumentException("Must provide a list of resultIds");
        }
        $params = $resultIds;
        $sql = "SELECT "
            . " arb.id, arb.assessment_id, arb.date, arb.data as 'result_data' "
            . " ,ab.data as 'assessment_data' "
            . " ,ai.id as 'assignmentitem_id' "
            . ",c.pid AS client_id "
            . " FROM " . self::TABLE_NAME . " arb "
            . "JOIN " . AssessmentRepository::TABLE_NAME . " ab ON (ab.id = arb.assessment_id) "
            . "JOIN " . PatientService::TABLE_NAME . " c ON (arb.client_id = c.pid) "
            . "LEFT JOIN " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . " ai ON (ai.assessmentresultblob_id = arb.id) "
            . "WHERE ";
        $sql .= " arb.id IN (" . str_repeat("?,", count($resultIds) - 1) . "?" . ") ";
        $sql .= "AND c.uuid = ? "
            . "ORDER BY arb.date DESC ";
        $params[] = UuidRegistry::uuidToBytes($clientId);
        $result = QueryUtils::fetchRecords($sql, $params);
        if (empty($result)) {
            return null;
        }
        $records = $this->hydrateRecordsFromResult($result);
        return $records;
    }
    public function getResultsForPatient(string $clientId, ?string $assessmentUID, ?string $resultId)
    {
        if (empty($assessmentUID) && empty($resultId)) {
            throw new \InvalidArgumentException("Must provide either an assessment UID or a result ID");
        }
        $params = [];
        $sql = "SELECT "
        . " arb.id, arb.assessment_id, arb.date, arb.data as 'result_data' "
            . " ,ab.data as 'assessment_data' "
            . " ,ai.id as 'assignmentitem_id' "
            . ",c.pid AS client_id "
            . " FROM " . self::TABLE_NAME . " arb "
            . "JOIN " . AssessmentRepository::TABLE_NAME . " ab ON (ab.id = arb.assessment_id) "
            . "JOIN " . PatientService::TABLE_NAME . " c ON (arb.client_id = c.pid) "
            . "LEFT JOIN " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM . " ai ON (ai.assessmentresultblob_id = arb.id) "
            . "WHERE ";
        if (!empty($assessmentUID)) {
            $sql .= "ab.uid = ? ";
            $params[] = $assessmentUID;
        } else {
            $sql .= "arb.id = ? ";
            $params[] = $resultId;
        }

        $sql .= "AND c.uuid = ? "
            . "ORDER BY arb.date DESC "
            . "LIMIT 1 ";

        $params[] = UuidRegistry::uuidToBytes($clientId);
        $result = QueryUtils::fetchRecords($sql, $params);
        if (empty($result)) {
            return null;
        }
        $records = $this->hydrateRecordsFromResult($result);
        return $records[0];
    }

    private function hydrateRecordsFromResult($result)
    {
        $records = [];
        foreach ($result as $resultBlob) {
            $resultData = json_decode($resultBlob['result_data'] ?? '{}', true);
            $assessmentData = json_decode($resultBlob['assessment_data'] ?? '{}', true);
            $resultData['_assessment'] = $assessmentData;
            $resultData['_assignmentItemId'] = $resultBlob['assignmentitem_id'];
            $resultData['_dateCompleted'] = $resultBlob['date'];
            $records[] = $resultData;
        }
        return $records;
    }

    public function createResult(string $resultId, array $resultData, int $clientId, int $assessmentId)
    {

        $sql = "INSERT INTO " . self::TABLE_NAME . " (id, assessment_id, client_id, data) VALUES (?, ?, ?, ?)";

        $sanitizer = new HTMLSanitizer();
        if (!empty($resultData['_answers'])) {
            $answers = [];
            foreach ($resultData['_answers'] as $answer) {
                $answers[] = [
                    '_answer' => $sanitizer->sanitize($answer['_answer'])
                    ,'_score' => intval($answer['_score'])
                    ,'_question_id' => $answer['_question_id']
                ];
            }
            $resultData['_answers'] = $answers;
        }
        $resultData['data']['_id'] = $resultId; // make sure we use the server id, not what is sent from the client.
        $resultBlob = json_encode($resultData['data'], JSON_THROW_ON_ERROR);
        $params = [$resultId, $assessmentId, $clientId,$resultBlob];
        // we don't do an insert here as we don't need an insert id since we are using string uuids here.
        QueryUtils::sqlStatementThrowException($sql, $params);
        return ['id' => $resultId, 'assessment_id' => $assessmentId, 'client_id' => $clientId, 'data' => $resultBlob];
    }

    private function insertOnSiteDocumentRecord($templateId, $pid, $qr, $questionnaireName)
    {
    /**
     * @see /portal/patient/scripts/onsitedocuments.js
    'pid': cpid,
    'facility': page.formOrigin, // 0 portal, 1 dashboard, 2 patient documents
    'provider': page.onsiteDocument.get('provider'),
    'encounter': page.onsiteDocument.get('encounter'),
    'createDate': new Date(),
    'docType': page.onsiteDocument.get('docType'),
    'patientSignedStatus': ptsignature ? '1' : '0',
    'patientSignedTime': ptsignature ? new Date() : '0000-00-00',
    'authorizeSignedTime': page.onsiteDocument.get('authorizeSignedTime'),
    'acceptSignedStatus': page.onsiteDocument.get('acceptSignedStatus'),
    'authorizingSignator': page.onsiteDocument.get('authorizingSignator'),
    'reviewDate': (!isPortal) ? new Date() : '0000-00-00',
    'denialReason': page.onsiteDocument.get('denialReason'),
    'authorizedSignature': page.onsiteDocument.get('authorizedSignature'),
    'patientSignature': ptsignature,
    'fullDocument': templateContent,
    'fileName': page.onsiteDocument.get('fileName'),
    'filePath': page.onsiteDocument.get('filePath'),
    'csrf_token_form': csrfTokenDoclib
     */
        $values = [
        'pid' => $pid
        ,'facility' => 0 // we will treat saving through this service as coming from the dashboard
        ,'provider' => 0
        ,'encounter' => $qr['encounter']
        ,'create_date' => (new \DateTime())->format("Y-m-d H:i:s")
        ,'doc_type' => $questionnaireName
        ,'patient_signed_status' => 0
        ,'patient_signed_time' => '0000-00-00'
        ,'authorize_signed_time' => '0000-00-00'
        ,'accept_signed_status' => 0
        ,'authorizing_signator' => 0
        ,'review_date' => (new \DateTime())->format("Y-m-d H:i:s")
        ,'denial_reason' => 'In Review'
        ,'authorized_signature' => ''
        ,'patient_signature' => '' // TODO: @adunsulag should we populate the patient signature if we have one.
        ,'full_document' => $qr['questionnaire_response'] // we could leave this empty
        ,'file_name' => $questionnaireName
        ,'file_path' => $templateId // this is the template_id
        ];
        $columnBinding = str_repeat("?,", count($values) - 1) . "?";
        $sql = "INSERT INTO onsite_documents(pid, facility, provider, encounter, create_date, doc_type"
        . ", patient_signed_status, patient_signed_time, authorize_signed_time,accept_signed_status, authorizing_signator"
        . ",review_date,denial_reason,authorized_signature, patient_signature, full_document,file_name,file_path) "
        . " VALUES (" . $columnBinding . ") ";
        return QueryUtils::sqlInsert($sql, array_values($values));
    }
}
