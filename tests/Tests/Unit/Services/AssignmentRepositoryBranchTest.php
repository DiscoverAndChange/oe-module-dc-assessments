<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed branch/edge tests for AssignmentRepository PUBLIC methods NOT already
 * exercised by AssignmentRepositoryCrudTest (save/read/complete/remove of a single
 * assessment assignment) or AssignmentRepositoryHydrationTest (the private hydrators).
 *
 * These cover the remaining fixture-able read/query surface:
 *   - getAssignmentItemsForAuditId (happy path via a completed item's audit record,
 *     plus the empty path for an unknown audit id)
 *   - getAssignmentForAssignmentItemUuid (found / unknown)
 *   - hasCompletedAssignments / hasCompletedAssignmentItems false<->true + empty edges
 *   - getAssignmentsForEncounterUuid empty + multiple
 *   - getAssignmentUuidsForAppointment / getAssignmentsForAppointmentId /
 *     getTemplateProfileAssignmentForAppointmentId null/empty/unknown guards
 *   - unlinkAssignmentFromAppointment no-op (no matching appointment linkage)
 *   - getQuestionnaireAssignmentItemsForClient / ...ForEncounter empty contracts
 *   - saveAssignmentForClient for an AssignedAssessmentGroup (group + child item)
 *
 * Seeding/cleanup mirrors AssignmentRepositoryCrudTest: a throwaway patient_data row
 * (pid==id + uuid), a 'phptest%' assessment, and every seeded row removed child-before
 * -parent in tearDown with the ignore-errors flag.
 */
class AssignmentRepositoryBranchTest extends TestCase
{
    private const UNKNOWN_UUID = '00000000-0000-4000-8000-0000000000fe';

    /** an onsite_portal_activity id / appointment id guaranteed not to exist in the test db. */
    private const UNKNOWN_ID = 999999999;

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
        $nextPid = QueryUtils::fetchSingleValue(
            "SELECT IFNULL(MAX(pid), 0) + 1 AS nextpid FROM " . PatientService::TABLE_NAME,
            'nextpid',
            []
        );
        $this->pid = (int) $nextPid;
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME . " (pid, fname, lname, date, pubpid) VALUES (?, ?, ?, NOW(), ?)",
            [$this->pid, 'phptest-fname', 'phptest-assignbranch', 'phptest-' . $this->pid]
        );
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
            'phptest-assignbranch-uid',
            'phptest Branch Assessment',
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
        // assessment groups are referenced by dac_Assignment.assessmentgroup_id, so they
        // go after the assignments above.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentGroupService::TABLE_NAME . " WHERE name LIKE 'phptest%'",
            [],
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

    /** Build an unsaved assessment-type assignment for the seeded patient/assessment. */
    private function buildAssessmentAssignment(): Assignment
    {
        $dateAssigned = new \DateTime();

        $item = new AssignedAssessment();
        $item->setDateAssigned($dateAssigned);
        $item->setName('phptest Branch Assessment');
        $item->setUid('phptest-assignbranch-uid');
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
    // getAssignmentItemsForAuditId
    // ------------------------------------------------------------------------

    public function testGetAssignmentItemsForAuditIdReturnsItemForCompletedAuditRecord(): void
    {
        $saved = $this->persistAssignment();
        $itemUuid = $saved->getItems()[0]->getId();

        // completing an item creates a real onsite_portal_activity row and stamps its id
        // onto the item as audit_id -- that is the id getAssignmentItemsForAuditId reads by.
        /** @var AssignedAssessment $item */
        $item = $this->repo->getAssignmentItem($itemUuid, $this->clientUuid);
        $completed = $this->repo->updateCompletedAssignmentItem($item);
        $auditId = $completed->getAuditId();
        $this->assertNotNull($auditId, 'completion must produce an audit id to look up by');

        $items = $this->repo->getAssignmentItemsForAuditId($auditId);
        $this->assertCount(1, $items);
        $this->assertSame($itemUuid, $items[0]->getId());
        $this->assertSame($auditId, $items[0]->getAuditId());
    }

    public function testGetAssignmentItemsForAuditIdReturnsEmptyForUnknownId(): void
    {
        // a fresh assignment exists but carries no audit id, and nothing matches the
        // unknown audit id -> an empty list regardless of other data in the db.
        $this->persistAssignment();
        $this->assertSame([], $this->repo->getAssignmentItemsForAuditId(self::UNKNOWN_ID));
    }

    // ------------------------------------------------------------------------
    // getAssignmentForAssignmentItemUuid
    // ------------------------------------------------------------------------

    public function testGetAssignmentForAssignmentItemUuidReturnsAssignment(): void
    {
        $saved = $this->persistAssignment();
        $itemUuid = $saved->getItems()[0]->getId();

        $assignment = $this->repo->getAssignmentForAssignmentItemUuid($itemUuid);
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertSame($saved->getId(), $assignment->getId());
    }

    public function testGetAssignmentForAssignmentItemUuidReturnsNullForUnknown(): void
    {
        $this->persistAssignment();
        $this->assertNull($this->repo->getAssignmentForAssignmentItemUuid(self::UNKNOWN_UUID));
    }

    // ------------------------------------------------------------------------
    // hasCompletedAssignments / hasCompletedAssignmentItems edges
    // ------------------------------------------------------------------------

    public function testHasCompletedAssignmentsTrueWhenPatientHasNoAssignments(): void
    {
        // vacuously true: nothing incomplete exists for a brand-new patient.
        $this->assertTrue($this->repo->hasCompletedAssignments($this->pid));
    }

    public function testHasCompletedAssignmentsFalseWhenAnIncompleteAssignmentExists(): void
    {
        $this->persistAssignment();
        $this->assertFalse($this->repo->hasCompletedAssignments($this->pid));
    }

    public function testHasCompletedAssignmentItemsTrueForUnknownAssignmentId(): void
    {
        // no items reference the unknown assignment id, so none are incomplete -> true.
        $this->assertTrue($this->repo->hasCompletedAssignmentItems(self::UNKNOWN_ID));
    }

    public function testHasCompletedAssignmentItemsFalseWhenAnItemIsIncomplete(): void
    {
        $saved = $this->persistAssignment();
        $assignmentIntId = (int) $this->repo->getAssignmentIdForAssignmentItem($saved->getItems()[0]);
        $this->assertFalse($this->repo->hasCompletedAssignmentItems($assignmentIntId));
    }

    // ------------------------------------------------------------------------
    // getAssignmentsForEncounterUuid (empty + multiple)
    // ------------------------------------------------------------------------

    public function testGetAssignmentsForEncounterUuidEmptyForNewPatient(): void
    {
        $this->assertSame([], $this->repo->getAssignmentsForEncounterUuid('', $this->clientUuid));
    }

    public function testGetAssignmentsForEncounterUuidReturnsAllForClient(): void
    {
        $first = $this->persistAssignment();
        $second = $this->persistAssignment();

        $assignments = $this->repo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(2, $assignments);
        $ids = array_map(static fn ($a) => $a->getId(), $assignments);
        $this->assertContains($first->getId(), $ids);
        $this->assertContains($second->getId(), $ids);
    }

    // ------------------------------------------------------------------------
    // appointment-link reads (guard / empty / unknown paths)
    // ------------------------------------------------------------------------

    public function testGetAssignmentUuidsForAppointmentReturnsNullForEmptyInput(): void
    {
        $this->assertNull($this->repo->getAssignmentUuidsForAppointment(null));
        $this->assertNull($this->repo->getAssignmentUuidsForAppointment(0));
    }

    public function testGetAssignmentUuidsForAppointmentReturnsNullForUnknownAppointment(): void
    {
        // no dac_Assignment row is linked to this pc_eid.
        $this->assertNull($this->repo->getAssignmentUuidsForAppointment(self::UNKNOWN_ID));
    }

    public function testGetAssignmentsForAppointmentIdGuards(): void
    {
        $this->assertNull($this->repo->getAssignmentsForAppointmentId(null));
        $this->assertNull($this->repo->getAssignmentsForAppointmentId(0));
        // a non-empty but unlinked pc_eid runs the search and finds nothing.
        $this->assertSame([], $this->repo->getAssignmentsForAppointmentId(self::UNKNOWN_ID));
    }

    public function testGetTemplateProfileAssignmentForAppointmentIdReturnsNullWhenNoneMatch(): void
    {
        $this->assertNull($this->repo->getTemplateProfileAssignmentForAppointmentId(null));
        $this->assertNull($this->repo->getTemplateProfileAssignmentForAppointmentId(self::UNKNOWN_ID));
    }

    public function testUnlinkAssignmentFromAppointmentIsNoOpWithoutMatchingLinkage(): void
    {
        $saved = $this->persistAssignment();

        // the assignment is not linked to any appointment, so the guarded UPDATE matches
        // no rows; the method still returns the assignment id and leaves the row intact.
        $returned = $this->repo->unlinkAssignmentFromAppointment($saved->getId(), self::UNKNOWN_UUID, 1);
        $this->assertSame($saved->getId(), $returned);
        $this->assertNotNull($this->repo->getAssignmentByUuid($saved->getId()));
    }

    // ------------------------------------------------------------------------
    // questionnaire item reads (empty contracts)
    // ------------------------------------------------------------------------

    public function testGetQuestionnaireAssignmentItemsForClientEmptyWhenNoQuestionnaires(): void
    {
        // the patient only has an assessment assignment, so no questionnaire items match.
        $this->persistAssignment();
        $items = $this->repo->getQuestionnaireAssignmentItemsForClient($this->pid, self::UNKNOWN_UUID);
        $this->assertSame([], $items);
    }

    public function testGetQuestionnaireAssignmentItemsForEncounterAlwaysEmpty(): void
    {
        // current contract: this method is a stub that always returns an empty list.
        $this->assertSame([], $this->repo->getQuestionnaireAssignmentItemsForEncounter('enc', 'questionnaire'));
    }

    // ------------------------------------------------------------------------
    // saveAssignmentForClient for an AssignedAssessmentGroup (group + child item)
    // ------------------------------------------------------------------------

    public function testSaveAssignmentForClientPersistsAssessmentGroupWithChildItem(): void
    {
        $groupService = new AssessmentGroupService();
        $group = $groupService->createGroup('phptest Branch Group', null);
        $groupId = (int) $group->getId();

        $dateAssigned = new \DateTime();
        $item = new AssignedAssessment();
        $item->setDateAssigned($dateAssigned);
        $item->setName('phptest Branch Assessment');
        $item->setUid('phptest-assignbranch-uid');
        $item->setAssessmentId($this->assessmentId);

        $groupAssignment = new AssignedAssessmentGroup();
        $groupAssignment->setDateAssigned($dateAssigned);
        $groupAssignment->setClientId($this->clientUuid);
        $groupAssignment->setAssessmentGroupId($groupId);
        $groupAssignment->addItem($item);

        $saved = $this->repo->saveAssignmentForClient($this->clientUuid, $groupAssignment, 1);
        $this->assertNotEmpty($saved->getId());
        $this->assertCount(1, $saved->getItems());

        // read it back: the assessmentgroup_id column routes hydration to a group type.
        $reloaded = $this->repo->getAssignmentByUuid($saved->getId());
        $this->assertInstanceOf(AssignedAssessmentGroup::class, $reloaded);
        $this->assertSame($groupId, $reloaded->getAssessmentGroupId());
        $this->assertSame('phptest Branch Group', $reloaded->getName());
        $this->assertCount(1, $reloaded->getItems());
        $this->assertInstanceOf(AssignedAssessment::class, $reloaded->getItems()[0]);
        $this->assertSame($this->assessmentId, $reloaded->getItems()[0]->getAssessmentId());
    }
}
