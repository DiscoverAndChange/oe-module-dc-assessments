<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientRepository;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed lifecycle tests for the HAPPY paths of ClientRepository.
 *
 * The guard/validation branches (empty clientId, no items, empty uid, empty asset id)
 * are covered without the DB by ClientRepositoryTest and are intentionally NOT repeated
 * here. These exercise the real SQL path against the oe-test-db harness:
 *   addGroupAssignmentToClient / addAssignmentToClient -> read back -> removeAssignmentFromClient
 * each against a freshly seeded patient so assertions are independent of pre-existing data.
 *
 * Every seeded row is prefixed with 'phptest' (assessment uid/name, group name) and keyed
 * to a throwaway patient_data row; tearDown removes all of it, child rows before parents.
 */
class ClientRepositoryCrudTest extends TestCase
{
    private ClientRepository $repo;

    private AssignmentRepository $assignmentRepo;

    /** pid of the throwaway patient_data row (dac_Assignment.client_id stores the pid). */
    private int $pid;

    /** canonical uuid string of the throwaway patient. */
    private string $clientUuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ClientRepository(new SystemLogger());
        $this->assignmentRepo = new AssignmentRepository();

        // --- seed a throwaway patient (pid == id) ----------------------------------
        $nextPid = QueryUtils::fetchSingleValue(
            "SELECT IFNULL(MAX(pid), 0) + 1 AS nextpid FROM " . PatientService::TABLE_NAME,
            'nextpid',
            []
        );
        $this->pid = (int) $nextPid;
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME . " (pid, fname, lname, date, pubpid) VALUES (?, ?, ?, NOW(), ?)",
            [$this->pid, 'phptest-fname', 'phptest-clientrepo', 'phptest-' . $this->pid]
        );
        // patient_data.pid is not auto-assigned; keep pid == id for the bare test seed.
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . PatientService::TABLE_NAME . " SET pid = id WHERE pid = ?",
            [$this->pid]
        );
        $this->pid = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . PatientService::TABLE_NAME . " WHERE pubpid = ?",
            'id',
            ['phptest-' . $this->pid]
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
        // group join rows, then the groups themselves.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentGroupService::ASSESSMENT_BLOB_JOIN_TABLE_NAME
            . " WHERE assessmentgroup_id IN (SELECT id FROM " . AssessmentGroupService::TABLE_NAME . " WHERE name LIKE 'phptest%')",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentGroupService::TABLE_NAME . " WHERE name LIKE 'phptest%'",
            [],
            true
        );
        // the leaf assessment blobs.
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

    /** Seed a published assessment and return its auto-increment id. */
    private function seedAssessment(string $uid, string $name): int
    {
        $assessmentRepo = new AssessmentRepository(new SystemLogger());
        /** @var int|string $id */
        $id = $assessmentRepo->createAssessment($uid, $name, 'phptest description', [], null);
        return (int) $id;
    }

    // ------------------------------------------------------------------------
    // addGroupAssignmentToClient
    // ------------------------------------------------------------------------

    public function testAddGroupAssignmentToClientPersistsGroupWithAllItems(): void
    {
        // a group with two seeded assessments.
        $id1 = $this->seedAssessment('phptest-group-uid-1', 'phptest Group Assessment 1');
        $id2 = $this->seedAssessment('phptest-group-uid-2', 'phptest Group Assessment 2');
        $this->assertGreaterThan(0, $id1);
        $this->assertGreaterThan(0, $id2);

        $groupService = new AssessmentGroupService();
        $group = $groupService->createGroup('phptest-group-lifecycle', null);
        $groupId = (int) $group->getId();
        $groupService->addAssessmentToGroup('phptest-group-uid-1', $groupId, null);
        $groupService->addAssessmentToGroup('phptest-group-uid-2', $groupId, null);

        // sanity: the seeded group really has two blobs to copy onto the assignment.
        $seededGroup = $groupService->getGroup($groupId);
        $this->assertCount(2, $seededGroup['assessmentGroupAssessmentBlobs']);

        $saved = $this->repo->addGroupAssignmentToClient($this->clientUuid, $groupId, 1, null);

        // the save assigns a uuid to the assignment and copies the group's name + items.
        $this->assertInstanceOf(Assignment::class, $saved);
        $this->assertNotEmpty($saved->getId(), 'saved group assignment should have a uuid');
        $this->assertSame('phptest-group-lifecycle', $saved->getName());
        $this->assertCount(2, $saved->getItems());
        foreach ($saved->getItems() as $item) {
            $this->assertInstanceOf(AssignedAssessment::class, $item);
            $this->assertNotEmpty($item->getId(), 'each saved item should have a uuid');
            $this->assertGreaterThan(0, $item->getAssessmentId());
        }

        // the patient is brand new, so the client's assignment list is exactly ours.
        $assignments = $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(1, $assignments);
        $this->assertSame($saved->getId(), $assignments[0]->getId());

        // reload by uuid and confirm both items persisted.
        $reloaded = $this->assignmentRepo->getAssignmentByUuid($saved->getId());
        $this->assertInstanceOf(Assignment::class, $reloaded);
        $this->assertCount(2, $reloaded->getItems());
    }

    // ------------------------------------------------------------------------
    // addAssignmentToClient
    // ------------------------------------------------------------------------

    public function testAddAssignmentToClientResolvesPublishedAssessmentAndPersists(): void
    {
        $assessmentId = $this->seedAssessment('phptest-single-uid', 'phptest Single Assessment');
        $this->assertGreaterThan(0, $assessmentId);

        // confirm the uid resolves to the just-published assessment id.
        $assessmentRepo = new AssessmentRepository(new SystemLogger());
        $this->assertSame(
            $assessmentId,
            (int) $assessmentRepo->getMostRecentAssessmentIdForUid('phptest-single-uid')
        );

        // build an assessment-type assignment with only a uid set; the repo resolves the id.
        $item = new AssignedAssessment();
        $item->setName('phptest Single Assessment');
        $item->setUid('phptest-single-uid');

        $assignment = new Assignment();
        $assignment->setClientId($this->clientUuid);
        $assignment->addItem($item);

        $saved = $this->repo->addAssignmentToClient($this->clientUuid, $assignment, 1);

        $this->assertNotEmpty($saved->getId(), 'saved assignment should have a uuid');
        $this->assertCount(1, $saved->getItems());
        /** @var AssignedAssessment $savedItem */
        $savedItem = $saved->getItems()[0];
        $this->assertInstanceOf(AssignedAssessment::class, $savedItem);
        $this->assertSame($assessmentId, $savedItem->getAssessmentId(), 'repo resolved the published assessment id from the uid');
        $this->assertNotEmpty($savedItem->getId());

        // read it back for the (brand new) patient.
        $assignments = $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(1, $assignments);
        $this->assertSame($saved->getId(), $assignments[0]->getId());

        $reloaded = $this->assignmentRepo->getAssignmentByUuid($saved->getId());
        $this->assertInstanceOf(Assignment::class, $reloaded);
        $this->assertSame('Assessment', $reloaded->getType());
        $this->assertCount(1, $reloaded->getItems());
        /** @var AssignedAssessment $reloadedItem */
        $reloadedItem = $reloaded->getItems()[0];
        $this->assertSame($assessmentId, $reloadedItem->getAssessmentId());
    }

    // ------------------------------------------------------------------------
    // removeAssignmentFromClient
    // ------------------------------------------------------------------------

    public function testRemoveAssignmentFromClientDeletesPreviouslyAddedAssignment(): void
    {
        $this->seedAssessment('phptest-remove-uid', 'phptest Remove Assessment');

        $item = new AssignedAssessment();
        $item->setName('phptest Remove Assessment');
        $item->setUid('phptest-remove-uid');

        $assignment = new Assignment();
        $assignment->setClientId($this->clientUuid);
        $assignment->addItem($item);

        $saved = $this->repo->addAssignmentToClient($this->clientUuid, $assignment, 1);
        $assignmentUuid = $saved->getId();

        // sanity: it is present before removal.
        $this->assertNotNull($this->assignmentRepo->getAssignmentByUuid($assignmentUuid));
        $this->assertCount(1, $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid));

        $returned = $this->repo->removeAssignmentFromClient($this->clientUuid, $assignmentUuid, 1, null);
        $this->assertSame($assignmentUuid, $returned);

        // assignment and the client's list are both gone.
        $this->assertNull($this->assignmentRepo->getAssignmentByUuid($assignmentUuid));
        $this->assertCount(0, $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid));
    }
}
