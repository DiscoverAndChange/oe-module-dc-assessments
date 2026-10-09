<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\QuestionnaireAssignmentListener;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskOnsitePortalActivityAccessService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AssignmentFixture;
use OpenEMR\Events\Services\ServiceSaveEvent;
use OpenEMR\Services\QuestionnaireResponseService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AssignmentFixture.php';

/**
 * End-to-end completion chain: a saved QuestionnaireResponse (ServiceSaveEvent) ->
 * QuestionnaireAssignmentListener -> generate the PDF (QuestionnaireResponseOnSiteDocumentService)
 * -> mark the questionnaire assignment item complete (AssignmentRepository).
 *
 * The onsite-portal-activity audit service is now injectable into AssignmentRepository, so it is
 * mocked here (no un-cleanable onsite_portal_activity row); every other step runs for real
 * against the DB, including the real PDF generation + document storage.
 */
class QuestionnaireAssignmentCompletionChainTest extends TestCase
{
    use AssignmentFixture;

    private string $questionnaireUuid = '';
    private string $responseId = '';
    private string $itemUuid = '';

    protected function setUp(): void
    {
        parent::setUp();
        // unique per run so the item-insert subquery (WHERE questionnaire_id = ?) can never
        // match more than one row even if a prior run leaked a fixture.
        $suffix = bin2hex(random_bytes(4));
        $this->questionnaireUuid = 'phptest-q-' . $suffix;
        $this->responseId = 'phptest-resp-' . $suffix;
        $this->seedPatientAndAssessment();
        $this->itemUuid = $this->seedQuestionnaireAssignmentItem($this->questionnaireUuid);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Order matters (child -> parent): the completed assignment item holds FKs to the
        // document, questionnaire_response and onsite_portal_activity rows, so remove the items
        // first, then the document + questionnaire_response, then the rest via the fixture helper.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM dac_AssignmentItem WHERE assignment_id IN (SELECT id FROM dac_Assignment WHERE client_id = ?)",
            [$this->fxPid]
        );
        QueryUtils::sqlStatementThrowException("DELETE FROM categories_to_documents WHERE document_id IN (SELECT id FROM documents WHERE foreign_id = ?)", [$this->fxPid]);
        QueryUtils::sqlStatementThrowException("DELETE FROM documents WHERE foreign_id = ?", [$this->fxPid]);
        QueryUtils::sqlStatementThrowException("DELETE FROM questionnaire_response WHERE response_id = ?", [$this->responseId]);
        $this->cleanupAssignmentFixtures();
    }

    public function testQuestionnaireSaveGeneratesPdfAndMarksAssignmentComplete(): void
    {
        // dac_AssignmentItem.audit_id FKs to onsite_portal_activity(id), so seed one real row
        // and point the (injected, mocked) audit service at its id — this exercises the
        // completion UPDATE without running the real audit service's internals.
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO onsite_portal_activity (patient_id, activity, narrative) VALUES (?, 'dc-assignment', 'phptest')",
            [$this->fxPid]
        );
        $auditId = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM onsite_portal_activity WHERE patient_id = ? ORDER BY id DESC LIMIT 1",
            'id',
            [$this->fxPid]
        );

        // dac_AssignmentItem.questionnaire_response_id FKs to questionnaire_response(response_id)
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO questionnaire_response (response_id, patient_id) VALUES (?, ?)",
            [$this->responseId, $this->fxPid]
        );

        $audit = $this->createMock(TaskOnsitePortalActivityAccessService::class);
        $audit->expects($this->once())
            ->method('createOnSitePortalActivity')
            ->willReturn($auditId);

        $assignmentRepo = new AssignmentRepository($audit);
        $pdfService = new QuestionnaireResponseOnSiteDocumentService(new QuestionnaireResponseService());
        $listener = new QuestionnaireAssignmentListener($assignmentRepo, $pdfService);

        $event = new ServiceSaveEvent(new QuestionnaireResponseService(), [
            'isNew' => true,
            'patient_id' => (string) $this->fxPid,
            'questionnaire_id' => $this->questionnaireUuid,
            'encounter' => '',
            'response_id' => $this->responseId,
            'questionnaire_name' => 'phptest Pipeline Questionnaire',
            'item' => [
                ['linkId' => 'q1', 'text' => 'How are you?', 'answer' => [['valueString' => 'Great']]],
            ],
        ]);

        $listener->updateQuestionnaireAssignments($event);

        // 1) a PDF document was generated + stored for the patient
        $docRow = QueryUtils::fetchRecords("SELECT id, mimetype FROM documents WHERE foreign_id = ? ORDER BY id DESC LIMIT 1", [$this->fxPid]);
        $this->assertNotEmpty($docRow, 'a document should have been created');
        $this->assertSame('application/pdf', $docRow[0]['mimetype']);

        // 2) the questionnaire assignment item is now complete (date_completed + audit stamped)
        /** @var AssignedQuestionnaire|null $reloaded */
        $reloaded = $assignmentRepo->getAssignmentItem($this->itemUuid, $this->fxClientUuid);
        $this->assertInstanceOf(AssignedQuestionnaire::class, $reloaded);
        $this->assertTrue($reloaded->getIsComplete(), 'the assignment item should be marked complete');

        $completedAt = QueryUtils::fetchSingleValue(
            "SELECT date_completed FROM dac_AssignmentItem WHERE uuid = ?",
            'date_completed',
            [UuidRegistry::uuidToBytes($this->itemUuid)]
        );
        $this->assertNotEmpty($completedAt, 'date_completed should be persisted');
    }
}
