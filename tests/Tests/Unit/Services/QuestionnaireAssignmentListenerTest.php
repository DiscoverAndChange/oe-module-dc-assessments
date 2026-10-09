<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Events\Services\ServiceSaveEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\QuestionnaireAssignmentListener;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AssignmentFixture;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AclIntegration.php';
require_once __DIR__ . '/../../Support/AssignmentFixture.php';

/**
 * DB-backed integration test for QuestionnaireAssignmentListener::updateQuestionnaireAssignments(),
 * the ServiceSaveEvent::EVENT_POST_SAVE reaction that marks a patient's questionnaire assignment
 * item complete once a QuestionnaireResponse is saved.
 *
 * The listener is driven with a REAL AssignmentRepository so the assignment-lookup DB path
 * (getQuestionnaireAssignmentItemsForClient) actually executes against the test DB, and the
 * out-of-scope PDF/document collaborator (QuestionnaireResponseOnSiteDocumentService) is MOCKED.
 *
 * Scope note: the shared AssignmentFixture seeds an AssignedAssessment item, but the listener's
 * completion branch only fires for AssignedQuestionnaire items (it filters with `instanceof
 * AssignedQuestionnaire`, and getQuestionnaireAssignmentItemsForClient joins on a `questionnaire`
 * row). Seeding a matching AssignedQuestionnaire would require a core `questionnaire` table row, a
 * document_template, and would additionally trip the un-mockable TaskOnsitePortalActivityAccessService
 * audit side effect inside AssignmentRepository::updateCompletedAssignmentItem. So the happy-path
 * document-generation branch is DEFERRED (see class-level report), and these tests cover the
 * reachable, deterministic behaviour: the listener's guard clauses and the "no matching
 * questionnaire assignment" branch — asserting the seeded item is left untouched and the mocked
 * document service is never invoked.
 */
class QuestionnaireAssignmentListenerTest extends TestCase
{
    use AclIntegration;
    use AssignmentFixture;

    private string $itemUuid = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
        $this->seedPatientAndAssessment();
        $this->itemUuid = $this->seedAssignmentItem();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupAssignmentFixtures();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function saveData(array $overrides = []): array
    {
        return array_merge([
            'isNew' => true,
            'patient_id' => (string) $this->fxPid,
            // a questionnaire uuid that matches no seeded item -> the lookup returns no items
            'questionnaire_id' => 'phptest-nonexistent-questionnaire-uuid',
            'encounter' => '',
            'response_id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'questionnaire_name' => 'phptest Pipeline Questionnaire',
        ], $overrides);
    }

    /**
     * Build a ServiceSaveEvent whose service reports as the given class (mocked, so no real
     * service construction happens) carrying the supplied save data.
     *
     * @param array<string, mixed> $overrides
     */
    private function buildEvent(array $overrides = [], string $serviceClass = QuestionnaireResponseService::class): ServiceSaveEvent
    {
        $service = $this->createMock($serviceClass);
        return new ServiceSaveEvent($service, $this->saveData($overrides));
    }

    private function makeListener(QuestionnaireResponseOnSiteDocumentService $docService): QuestionnaireAssignmentListener
    {
        // REAL repository so the assignment-completion DB path runs; MOCKED PDF/document service.
        return new QuestionnaireAssignmentListener(new AssignmentRepository(), $docService);
    }

    /** Reload the seeded assignment item from the DB to observe its completion state. */
    private function reloadSeededItem(): ?Assignment
    {
        return (new AssignmentRepository())->getAssignmentItem($this->itemUuid, $this->fxClientUuid);
    }

    public function testNoMatchingQuestionnaireAssignmentLeavesItemIncompleteAndSkipsDocument(): void
    {
        $docService = $this->createMock(QuestionnaireResponseOnSiteDocumentService::class);
        // out-of-scope collaborator must NOT be touched when there is no matching questionnaire item
        $docService->expects($this->never())->method('createDocument');

        $listener = $this->makeListener($docService);
        $result = $listener->updateQuestionnaireAssignments($this->buildEvent());

        // no AssignedQuestionnaire item matched -> listener completes nothing and returns nothing
        $this->assertNull($result, 'no matching questionnaire assignment -> nothing returned');

        // sanity: the real lookup ran and the seeded (assessment) item is still present + incomplete
        $item = $this->reloadSeededItem();
        $this->assertNotNull($item, 'seeded assignment item should still exist');
        $this->assertFalse($item->getIsComplete(), 'seeded item must remain incomplete');
    }

    public function testUpdateRequestIsNotNewReturnsEarlyAndSkipsDocument(): void
    {
        $docService = $this->createMock(QuestionnaireResponseOnSiteDocumentService::class);
        $docService->expects($this->never())->method('createDocument');

        $listener = $this->makeListener($docService);
        // isNew === false -> the listener bails before any assignment lookup
        $result = $listener->updateQuestionnaireAssignments($this->buildEvent(['isNew' => false]));

        $this->assertNull($result);
        $this->assertFalse($this->reloadSeededItem()->getIsComplete(), 'update (non-new) must not complete anything');
    }

    public function testNonQuestionnaireResponseServiceEventIsIgnored(): void
    {
        $docService = $this->createMock(QuestionnaireResponseOnSiteDocumentService::class);
        $docService->expects($this->never())->method('createDocument');

        $listener = $this->makeListener($docService);
        // a save from a different service (not QuestionnaireResponseService) -> listener no-ops
        $result = $listener->updateQuestionnaireAssignments($this->buildEvent([], PatientService::class));

        $this->assertNull($result);
        $this->assertFalse($this->reloadSeededItem()->getIsComplete());
    }
}
