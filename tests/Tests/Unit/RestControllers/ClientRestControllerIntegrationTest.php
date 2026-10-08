<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\ClientRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientMessageDispatcher;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;

require_once __DIR__ . '/../../Support/AclIntegration.php';

/**
 * DB-backed integration test for the ACL-gated write actions of ClientRestController:
 *   list, addAssignmentGroupToClient, addAssignmentToClient, removeAssignmentFromClient,
 *   sendMessageToClient.
 *
 * These gate on AclMain::aclCheckCore(), so loginAsAdmin() installs OpenEMR's default ACL
 * tree once and logs in the seeded super user (id=1, "phptestuser"); without it every action
 * short-circuits to access-denied and the write bodies never run.
 *
 * Each test runs against a freshly seeded throwaway patient (pid == id, out-of-band uuid) so
 * assertions are independent of pre-existing data. ClientRestController delegates to
 * ClientRepository, so the fixtures mirror ClientRepositoryCrudTest. Every seeded row carries
 * a phptest% prefix / the throwaway pid; tearDown removes child rows before parents.
 *
 * sendMessageToClient is exercised with a MOCKED ClientMessageDispatcher so no real email /
 * notification is dispatched.
 */
class ClientRestControllerIntegrationTest extends TestCase
{
    use AclIntegration;

    private ClientRepository $repo;

    private AssignmentRepository $assignmentRepo;

    /** pid of the throwaway patient_data row (dac_Assignment.client_id stores the pid). */
    private int $pid;

    /** canonical uuid string of the throwaway patient (what the controller receives as $id). */
    private string $clientUuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
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
            "INSERT INTO " . PatientService::TABLE_NAME . " (pid, fname, lname, email, date, pubpid) VALUES (?, ?, ?, ?, NOW(), ?)",
            [$this->pid, 'phptest-fname', 'phptest-clientctrl', 'phptest-client@example.test', 'phptest-' . $this->pid]
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

    // ------------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------------

    /** Build the controller with a mocked ClientMessageDispatcher (no real mail is sent). */
    private function newController(?ClientMessageDispatcher $dispatcher = null): ClientRestController
    {
        return new ClientRestController(
            new SystemLogger(),
            $dispatcher ?? $this->createMock(ClientMessageDispatcher::class)
        );
    }

    /**
     * Build a ServerRestRequest carrying query params (for list()). jsonRequest() from the
     * trait leaves getQueryParams() unstubbed (it returns null, violating the : array return
     * type), so list() needs its own request with the query bag stubbed.
     *
     * @param array<string, mixed> $query
     */
    private function listRequest(array $query = []): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getQueryParams')->willReturn($query);
        $inner->method('getRequestUserId')->willReturn(self::ACL_USER_ID);
        $inner->method('getContent')->willReturn('');
        $inner->headers = new HeaderBag();
        $inner->method('isPatientRequest')->willReturn(false);
        $inner->method('getUri')->willReturn('/api/v1/clients');
        return new ServerRestRequest($inner);
    }

    /** Seed a published assessment and return its auto-increment id. */
    private function seedAssessment(string $uid, string $name): int
    {
        $assessmentRepo = new AssessmentRepository(new SystemLogger());
        /** @var int|string $id */
        $id = $assessmentRepo->createAssessment($uid, $name, 'phptest description', [], null);
        return (int) $id;
    }

    /** @return array<string, mixed> */
    private function decode(\Psr\Http\Message\ResponseInterface $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true) ?: [];
        return $decoded;
    }

    // ------------------------------------------------------------------------
    // list
    // ------------------------------------------------------------------------

    public function testListReturnsPaginatedResultsEnvelope(): void
    {
        // admin/super passes both aclCheckCore('patients','demo') and aclCheckCore('admin','super'),
        // so the empty-user branch is allowed and the search runs.
        $response = $this->newController()->list($this->listRequest([]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = $this->decode($response);
        $this->assertArrayHasKey('results', $body);
        $this->assertIsArray($body['results']);
        $this->assertArrayHasKey('totalCount', $body);
        $this->assertArrayHasKey('_limit', $body);
    }

    // ------------------------------------------------------------------------
    // addAssignmentGroupToClient
    // ------------------------------------------------------------------------

    public function testAddAssignmentGroupToClientPersistsGroupAssignment(): void
    {
        $this->seedAssessment('phptest-ctrl-group-uid-1', 'phptest Ctrl Group Assessment 1');
        $this->seedAssessment('phptest-ctrl-group-uid-2', 'phptest Ctrl Group Assessment 2');

        $groupService = new AssessmentGroupService();
        $group = $groupService->createGroup('phptest-ctrl-group', null);
        $groupId = (int) $group->getId();
        $groupService->addAssessmentToGroup('phptest-ctrl-group-uid-1', $groupId, null);
        $groupService->addAssessmentToGroup('phptest-ctrl-group-uid-2', $groupId, null);

        $request = $this->jsonRequest(['id' => $groupId]);
        $response = $this->newController()->addAssignmentGroupToClient($request, $this->clientUuid);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = $this->decode($response);
        $this->assertArrayHasKey('assignment', $body);
        $this->assertNotEmpty($body['assignment']['id'] ?? null, 'saved group assignment should carry a uuid');

        // the patient is brand new, so its assignment list is exactly the one we just added.
        $assignments = $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(1, $assignments);
        $this->assertSame('phptest-ctrl-group', $assignments[0]->getName());
        $this->assertCount(2, $assignments[0]->getItems());
    }

    public function testAddAssignmentGroupToClientWithoutGroupIdReturnsError(): void
    {
        // empty body -> getBodyAsJson() == null -> groupId empty -> InvalidArgumentException.
        $response = $this->newController()->addAssignmentGroupToClient($this->jsonRequest([]), $this->clientUuid);

        $this->assertSame(500, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(0, $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid));
    }

    // ------------------------------------------------------------------------
    // addAssignmentToClient
    // ------------------------------------------------------------------------

    public function testAddAssignmentToClientPersistsAssignment(): void
    {
        $assessmentId = $this->seedAssessment('phptest-ctrl-single-uid', 'phptest Ctrl Single Assessment');

        $body = [
            'type' => 'Assessment',
            'name' => 'phptest Ctrl Single Assessment',
            'items' => [
                ['type' => 'Assessment', 'name' => 'phptest Ctrl Single Assessment', 'uid' => 'phptest-ctrl-single-uid'],
            ],
        ];
        $response = $this->newController()->addAssignmentToClient($this->jsonRequest($body), $this->clientUuid);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $decoded = $this->decode($response);
        $this->assertArrayHasKey('assignment', $decoded);

        $assignments = $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid);
        $this->assertCount(1, $assignments);
        $this->assertSame('Assessment', $assignments[0]->getType());
        $this->assertCount(1, $assignments[0]->getItems());
        /** @var AssignedAssessment $savedItem */
        $savedItem = $assignments[0]->getItems()[0];
        $this->assertSame($assessmentId, $savedItem->getAssessmentId(), 'repo resolved the published assessment id from the uid');
    }

    // ------------------------------------------------------------------------
    // removeAssignmentFromClient
    // ------------------------------------------------------------------------

    public function testRemoveAssignmentFromClientDeletesAssignment(): void
    {
        // seed an assignment to remove (via the repo, like ClientRepositoryCrudTest).
        $this->seedAssessment('phptest-ctrl-remove-uid', 'phptest Ctrl Remove Assessment');
        $item = new AssignedAssessment();
        $item->setName('phptest Ctrl Remove Assessment');
        $item->setUid('phptest-ctrl-remove-uid');
        $assignment = new Assignment();
        $assignment->setClientId($this->clientUuid);
        $assignment->addItem($item);
        $saved = $this->repo->addAssignmentToClient($this->clientUuid, $assignment, self::ACL_USER_ID);
        $assignmentUuid = $saved->getId();

        // sanity: present before removal.
        $this->assertNotNull($this->assignmentRepo->getAssignmentByUuid($assignmentUuid));

        $response = $this->newController()->removeAssignmentFromClient(
            $this->jsonRequest([]),
            $this->clientUuid,
            $assignmentUuid
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = $this->decode($response);
        $this->assertSame($assignmentUuid, $body['assignmentId'] ?? null);

        // gone afterwards.
        $this->assertNull($this->assignmentRepo->getAssignmentByUuid($assignmentUuid));
        $this->assertCount(0, $this->assignmentRepo->getAssignmentsForEncounterUuid('', $this->clientUuid));
    }

    // ------------------------------------------------------------------------
    // sendMessageToClient
    // ------------------------------------------------------------------------

    public function testSendMessageToClientDispatchesViaDispatcher(): void
    {
        $dispatcher = $this->createMock(ClientMessageDispatcher::class);
        // the patient pid is resolved from the uuid inside the action; assert the dispatcher
        // is invoked exactly once so no real mailer / notification is hit.
        $dispatcher->expects($this->once())
            ->method('sendInvitationMessage')
            ->with(
                $this->equalTo($this->pid),
                $this->equalTo('phptest subject'),
                $this->equalTo('phptest message body'),
                $this->anything(),
                $this->anything(),
                $this->equalTo(false)
            );

        $request = $this->jsonRequest([
            'message' => 'phptest message body',
            'subject' => 'phptest subject',
        ]);

        // NOTE: on the success path the action returns null (not a ResponseInterface); see the
        // latent-bug note in the hand-off. The dispatcher expectation above is the assertion.
        $response = $this->newController($dispatcher)->sendMessageToClient($request, $this->clientUuid);
        $this->assertNull($response, 'sendMessageToClient returns null on success (see latent-bug note)');
    }

    public function testSendMessageToClientWithEmptyMessageReturnsValidationError(): void
    {
        $dispatcher = $this->createMock(ClientMessageDispatcher::class);
        $dispatcher->expects($this->never())->method('sendInvitationMessage');

        $response = $this->newController($dispatcher)->sendMessageToClient(
            $this->jsonRequest(['message' => '   ', 'subject' => 'phptest subject']),
            $this->clientUuid
        );

        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
    }
}
