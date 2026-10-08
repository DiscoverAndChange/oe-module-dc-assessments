<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentGroupRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for AssessmentGroupRestController::list against the oe-test-db harness.
 *
 * list() loads company-visible groups via AssessmentGroupService::getAllGroups and merges
 * in document-template profile groups, returning a JSON array of AssessmentGroup objects.
 * A group created with a NULL company id is visible regardless of the resolved facility,
 * so the assertion is independent of pre-existing data.
 *
 * The request is stubbed as a patient request so getAuthRole() resolves to Role::Client
 * without reaching AclMain (keeping the test deterministic); a Client role simply forces
 * showAllGroups=false, which is the default path anyway.
 */
class AssessmentGroupRestControllerTest extends TestCase
{
    private string $groupName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupName = 'phptest-group-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentGroupService::TABLE_NAME . " WHERE name LIKE 'phptest-%'",
            [],
            true
        );
    }

    private function request(): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getQueryParams')->willReturn([]);
        $inner->method('isPatientRequest')->willReturn(true);
        return new ServerRestRequest($inner);
    }

    public function testListReturnsSeededGroup(): void
    {
        (new AssessmentGroupService())->createGroup($this->groupName, null);

        $response = (new AssessmentGroupRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        // AssessmentGroup::jsonSerialize() emits the group name.
        $this->assertStringContainsString($this->groupName, $body);
    }
}
