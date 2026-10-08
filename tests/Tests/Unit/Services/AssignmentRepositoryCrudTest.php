<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed lifecycle tests for the PUBLIC CRUD surface of AssignmentRepository
 * (the private hydrators are covered separately by AssignmentRepositoryHydrationTest).
 *
 * These exercise the real SQL path against the oe-test-db harness:
 *   save -> read back -> complete -> remove
 * for an assessment-type assignment attached to a freshly seeded patient.
 *
 * Seeding is prefixed with 'phptest' (assessment uid/name) and keyed to a throwaway
 * patient_data row created in setUp(); every seeded row is removed in tearDown(),
 * child rows before parents to respect the foreign keys.
 */
class AssignmentRepositoryCrudTest extends TestCase
{
    private const UNKNOWN_UUID = '00000000-0000-4000-8000-0000000000ff';

    private AssignmentRepository $repo;

    /** pid of the throwaway patient_data row (dac_Assignment.client_id stores the pid). */
    private int $pid;

    /** canonical uuid string of the throwaway patient. */
    private string $clientUuid;

    /** id of the throwaway dac_AssessmentBlob row used as the assignment item target. */
    private int $assessmentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AssignmentRepository();

        // --- seed a throwaway patient ------------------------------------------------
        // patient_data.pid is not auto-assigned by the schema, so take the next free value.
        $nextPid = QueryUtils::fetchSingleValue(
            "SELECT IFNULL(MAX(pid), 0) + 1 AS nextpid FROM " . PatientService::TABLE_NAME,
            'nextpid',
            []
        );
        $this->pid = (int) $nextPid;
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME . " (pid, fname, lname, date, pubpid) VALUES (?, ?, ?, NOW(), ?)",
            [$this->pid, 'phptest-fname', 'phptest-assignrepo', 'phptest-' . $this->pid]
        );
        // OpenEMR assigns patient uuids out of band; the bare test seed has none.
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        /** @var string $uuidBytes */
        $uuidBytes = QueryUtils::fetchSingleValue(
            "SELECT uuid FROM " . PatientService::TABLE_NAME . " WHERE pid = ?",
            'uuid',
            [$this->pid]
        );
        $this->clientUuid = UuidRegistry::uuidToString($uuidBytes);

        // --- seed a throwaway assessment (the item the assignment points at) --------
        $assessmentRepo = new AssessmentRepository(new SystemLogger());
        /** @var int|string $assessmentId */
        $assessmentId = $assessmentRepo->createAssessment(
            'phptest-assignrepo-uid',
            'phptest Assignment Assessment',
            'phptest description',
            [],
            null
        );
        $this->assessmentId = (int) $assessmentId;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // child rows first: assignment items for any assignment belonging to our patient.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssignmentRepository::TABLE_NAME_ASSIGNMENT_ITEM
            . " WHERE assignment_id IN (SELECT id FROM " . AssignmentRepository::TABLE_NAME . " WHERE client_id = ?)",
            [$this->pid],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssignmentRepository::TABLE_NAME . " WHERE client_id = ?",
            [$this->pid],
            true
        );
        // audit rows written by the completion path are keyed to the patient pid.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM onsite_portal_activity WHERE patient_id = ?",
            [$this->pid],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid LIKE 'phptest%'",
            [],
            true
        );
        // parent patient row last.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . PatientService::TABLE_NAME . " WHERE pid = ?",
            [$this->pid],
            true
        );
    }

    /**
     * Build an unsaved assessment-type assignment for the seeded patient/assessment,
     * mirroring how ClientRepository::addAssignmentToClient assembles one.
     */
    private function buildAssessmentAssignment(): Assignment
    {
        $dateAssigned = new \DateTime();

        $item = new AssignedAssessment();
        $item->setDateAssigned($dateAssigned);
        $item->setName('phptest Assignment Assessment');
        $item->setUid('phptest-assignrepo-uid');
        $item->setAssessmentId($this->assessmentId);

        $assignment = new Assignment();
        $assignment->setDateAssigned($dateAssigned);
        $assignment->setClientId($this->clientUuid);
        $assignment->addItem($item);

        return $assignment;
    }

    /** Persist a fresh assessment-type assignment and return the saved model. */
    private function persistAssignment(): Assignment
    {
        return $this->repo->saveAssignmentForClient($this->clientUuid, $this->buildAssessmentAssignment(), 1);
    }

    // ------------------------------------------------------------------------
    // save
    // ------------------------------------------------------------------------

    public function testSaveAssignmentForClientPersistsAssignmentAndItem(): void
    {
        $saved = $this->persistAssignment();

        // the save assigns a uuid to the assignment and to its single item.
        $this->assertNotEmpty($saved->getId(), 'saved assignment should have a uuid');
        $this->assertCount(1, $saved->getItems());
        $this->assertNotEmpty($saved->getItems()[0]->getId(), 'saved item should have a uuid');

        // read it straight back by uuid and confirm the item hydrates to our assessment.
        $reloaded = $this->repo->getAssignmentByUuid($saved->getId());
        $this->assertInstanceOf(Assignment::class, $reloaded);
        $this->assertSame($saved->getId(), $reloaded->getId());
        $this->assertSame('Assessment', $reloaded->getType());
        $this->assertFalse($reloaded->getIsComplete(), 'a freshly saved assignment is not complete');
        $this->assertCount(1, $reloaded->getItems());

        $item = $reloaded->getItems()[0];
        $this->assertInstanceOf(AssignedAssessment::class, $item);
        $this->assertSame($this->assessmentId, $item->getAssessmentId());
        $this->assertSame('phptest Assignment Assessment', $item->getName());
        $this->assertSame('phptest-assignrepo-uid', $item->getUid());
        $this->assertInstanceOf(\DateTime::class, $item->getDateAssigned());
    }

    public function testSaveAssignmentWithNoItemsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No items to save for assignment');

        $empty = new Assignment();
        $empty->setDateAssigned(new \DateTime());
        $this->repo->saveAssignmentForClient($this->clientUuid, $empty, 1);
    }

    // ------------------------------------------------------------------------
    // read
    // ------------------------------------------------------------------------

    public function testGetAssignmentsForClientReturnsSavedAssignment(): void
    {
        $saved = $this->persistAssignment();

        // the patient is brand new, so the client's assignment list is exactly ours.
        $assignments = $this->repo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(1, $assignments);
        $this->assertSame($saved->getId(), $assignments[0]->getId());
        $this->assertSame($this->clientUuid, $assignments[0]->getClientId());
    }

    public function testGetAssignmentItemReturnsItemForClient(): void
    {
        $saved = $this->persistAssignment();
        $itemUuid = $saved->getItems()[0]->getId();

        $item = $this->repo->getAssignmentItem($itemUuid, $this->clientUuid);
        $this->assertInstanceOf(AssignedAssessment::class, $item);
        $this->assertSame($itemUuid, $item->getId());
        $this->assertSame($this->assessmentId, $item->getAssessmentId());
        $this->assertSame($this->clientUuid, $item->getClientId());
    }

    public function testGetAssignmentItemReturnsNullForUnknownItem(): void
    {
        $this->persistAssignment();
        $this->assertNull($this->repo->getAssignmentItem(self::UNKNOWN_UUID, $this->clientUuid));
    }

    // ------------------------------------------------------------------------
    // complete
    // ------------------------------------------------------------------------

    public function testCompleteAssignmentItemSetsCompletedDateAndCascadesToAssignment(): void
    {
        $saved = $this->persistAssignment();
        $itemUuid = $saved->getItems()[0]->getId();

        // reload the item so it carries its clientId (the completion path audits against it).
        /** @var AssignedAssessment $item */
        $item = $this->repo->getAssignmentItem($itemUuid, $this->clientUuid);
        $this->assertNull($item->getDateCompleted(), 'item starts uncompleted');

        $completed = $this->repo->updateCompletedAssignmentItem($item);
        $this->assertInstanceOf(\DateTime::class, $completed->getDateCompleted());
        $this->assertNotNull($completed->getAuditId(), 'completion creates an audit record');

        // the item's completion is persisted and reads back.
        /** @var AssignedAssessment $reloadedItem */
        $reloadedItem = $this->repo->getAssignmentItem($itemUuid, $this->clientUuid);
        $this->assertInstanceOf(\DateTime::class, $reloadedItem->getDateCompleted());

        // because every item is complete, the parent assignment is marked complete too.
        $reloadedAssignment = $this->repo->getAssignmentByUuid($saved->getId());
        $this->assertTrue($reloadedAssignment->getIsComplete());

        $assignmentIntId = $this->repo->getAssignmentIdForAssignmentItem($item);
        $this->assertTrue($this->repo->hasCompletedAssignmentItems((int) $assignmentIntId));
        $this->assertTrue($this->repo->hasCompletedAssignments($this->pid));
    }

    // ------------------------------------------------------------------------
    // remove
    // ------------------------------------------------------------------------

    public function testRemoveAssignmentDeletesAssignmentAndItems(): void
    {
        $saved = $this->persistAssignment();
        $itemUuid = $saved->getItems()[0]->getId();

        // sanity: it is there before removal.
        $this->assertNotNull($this->repo->getAssignmentByUuid($saved->getId()));

        $returned = $this->repo->removeAssignment($this->clientUuid, $saved->getId(), 1);
        $this->assertSame($saved->getId(), $returned);

        // assignment, its item, and the client's list are all gone.
        $this->assertNull($this->repo->getAssignmentByUuid($saved->getId()));
        $this->assertNull($this->repo->getAssignmentItem($itemUuid, $this->clientUuid));
        $this->assertCount(0, $this->repo->getAssignmentsForEncounterUuid('', $this->clientUuid));
    }
}
