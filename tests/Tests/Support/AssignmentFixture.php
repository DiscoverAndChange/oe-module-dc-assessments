<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Services\PatientService;

/**
 * Shared DB fixtures for the questionnaire -> assignment-completion pipeline tests.
 *
 * Seeds a throwaway patient (pid forced == id so both dac_Assignment.client_id=pid and the
 * result-blob client_id->patient_data.id FK are satisfied), a published assessment, and a
 * saved assignment carrying one AssignedAssessment item. Everything is prefixed 'phptest'
 * and removed by cleanupAssignmentFixtures() in tearDown (child rows before parents).
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait AssignmentFixture
{
    protected int $fxPid = 0;
    protected string $fxClientUuid = '';
    protected int $fxAssessmentId = 0;
    protected string $fxAssessmentUid = '';

    /** Seed a patient (pid == id) + a published assessment. Sets $fxPid/$fxClientUuid/$fxAssessmentId. */
    protected function seedPatientAndAssessment(string $uid = 'phptest-pipeline-uid'): void
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME . " (fname, lname, date, pubpid) VALUES ('phptest', 'phptest-pipeline', NOW(), 'phptest-pipeline')"
        );
        $this->fxPid = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . PatientService::TABLE_NAME . " WHERE lname = 'phptest-pipeline' ORDER BY id DESC LIMIT 1",
            'id',
            []
        );
        // the result-blob/assignment code keys client rows on pid; force pid == id so the
        // client_id -> patient_data.id FK resolves.
        QueryUtils::sqlStatementThrowException("UPDATE " . PatientService::TABLE_NAME . " SET pid = ? WHERE id = ?", [$this->fxPid, $this->fxPid]);
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        /** @var string $uuidBytes */
        $uuidBytes = QueryUtils::fetchSingleValue("SELECT uuid FROM " . PatientService::TABLE_NAME . " WHERE pid = ?", 'uuid', [$this->fxPid]);
        $this->fxClientUuid = UuidRegistry::uuidToString($uuidBytes);

        $this->fxAssessmentUid = $uid;
        $this->fxAssessmentId = (int) (new AssessmentRepository(new SystemLogger()))
            ->createAssessment($uid, 'phptest Pipeline Assessment', 'phptest desc', [], null);
    }

    /**
     * Save an assignment with one AssignedAssessment item for the seeded patient.
     * Returns the saved assignment item's uuid string.
     */
    protected function seedAssignmentItem(): string
    {
        $dateAssigned = new \DateTime();
        $item = new AssignedAssessment();
        $item->setDateAssigned($dateAssigned);
        $item->setName('phptest Pipeline Assessment');
        $item->setUid($this->fxAssessmentUid);
        $item->setAssessmentId($this->fxAssessmentId);
        $assignment = new Assignment();
        $assignment->setDateAssigned($dateAssigned);
        $assignment->setName('phptest Pipeline Assignment');
        $assignment->setType('Assessment');
        $assignment->setClientId($this->fxClientUuid);
        $assignment->addItem($item);

        $saved = (new AssignmentRepository())->saveAssignmentForClient($this->fxClientUuid, $assignment, 1);
        return $saved->getItems()[0]->getId();
    }

    /**
     * Seed a core questionnaire_repository row + a saved assignment carrying one
     * AssignedQuestionnaire item linked to it (so getQuestionnaireAssignmentItemsForClient
     * resolves it). Returns the saved assignment item's uuid string.
     */
    protected function seedQuestionnaireAssignmentItem(string $questionnaireUuid): string
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO questionnaire_repository (questionnaire_id, name) VALUES (?, 'phptest Pipeline Questionnaire')",
            [$questionnaireUuid]
        );

        $dateAssigned = new \DateTime();
        $item = new AssignedQuestionnaire();
        $item->setDateAssigned($dateAssigned);
        $item->setName('phptest Pipeline Questionnaire');
        $item->setQuestionnaireId($questionnaireUuid);
        // leave documentTemplateId null: dac_AssignmentItem.document_template_id FKs to
        // document_templates(id), and we are not seeding a template row.
        $assignment = new Assignment();
        $assignment->setDateAssigned($dateAssigned);
        $assignment->setName('phptest Pipeline Q Assignment');
        $assignment->setType('Questionnaire');
        $assignment->setClientId($this->fxClientUuid);
        $assignment->addItem($item);

        $saved = (new AssignmentRepository())->saveAssignmentForClient($this->fxClientUuid, $assignment, 1);
        return $saved->getItems()[0]->getId();
    }

    protected function cleanupAssignmentFixtures(): void
    {
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM
            . " WHERE assignment_id IN (SELECT id FROM " . AssignmentRepository::TABLE_NAME . " WHERE client_id = ?)",
            [$this->fxPid],
            true
        );
        QueryUtils::sqlStatementThrowException("DELETE FROM " . AssignmentRepository::TABLE_NAME . " WHERE client_id = ?", [$this->fxPid], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM dac_AssessmentResultBlob WHERE assessment_id IN (SELECT id FROM dac_AssessmentBlob WHERE uid LIKE 'phptest%')", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM dac_AssessmentBlob WHERE uid LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM questionnaire_repository WHERE name LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM onsite_portal_activity WHERE patient_id = ?", [$this->fxPid], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM " . PatientService::TABLE_NAME . " WHERE lname = 'phptest-pipeline'", [], true);
    }
}
