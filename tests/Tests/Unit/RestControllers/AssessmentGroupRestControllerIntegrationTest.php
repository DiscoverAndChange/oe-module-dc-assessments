<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentGroupRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AclIntegration.php';

/**
 * DB-backed integration test for the ACL-gated AssessmentGroupRestController write actions
 * (create / addAssessmentToGroup / updateAssessmentVersionForGroup).
 *
 * loginAsAdmin() installs OpenEMR's default ACL tree + logs in the seeded admin, so
 * AclMain::aclCheckCore("encounters","forms") passes and getAuthRole() resolves to
 * Role::SuperUser — which drives every action down the companyId=null path. Without the
 * trait these actions only ever return an access-denied response.
 *
 * All seeded rows use a phptest% prefix and are removed in tearDown, child rows (the group
 * <-> assessment join) before the parents, so the suite is independent of pre-existing data.
 */
class AssessmentGroupRestControllerIntegrationTest extends TestCase
{
    use AclIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // child join rows first (FK to both group and assessment), then the parents.
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
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid LIKE 'phptest%'",
            [],
            true
        );
    }

    public function testCreatePersistsGroup(): void
    {
        $name = 'phptest-grp-' . bin2hex(random_bytes(4));
        $controller = new AssessmentGroupRestController();

        $response = $controller->create($this->jsonRequest(['name' => $name]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $found = ProcessingResult::extractDataArray((new AssessmentGroupService())->search(['name' => $name]));
        $this->assertNotEmpty($found, 'created group should be persisted and searchable');
    }

    public function testCreateWithMissingNameReturns400(): void
    {
        // no 'name' -> DATABASE_INSERT_CONTEXT validation fails -> VALIDATION_FAILED (400).
        $controller = new AssessmentGroupRestController();
        $response = $controller->create($this->jsonRequest([]));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddAssessmentToGroupPersistsJoin(): void
    {
        $uid = 'phptest-ga-' . bin2hex(random_bytes(4)); // <= 32 chars for the uid rule
        (new AssessmentRepository(new SystemLogger()))
            ->createAssessment($uid, 'phptest Grouped Assessment', 'desc', [], null);
        $group = (new AssessmentGroupService())->createGroup('phptest-grp-add-' . bin2hex(random_bytes(4)), null);
        $groupId = $group->getId();

        $controller = new AssessmentGroupRestController();
        $response = $controller->addAssessmentToGroup($this->jsonRequest(['uid' => $uid]), $groupId);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $joins = QueryUtils::fetchRecords(
            "SELECT agab.assessmentblob_id FROM " . AssessmentGroupService::ASSESSMENT_BLOB_JOIN_TABLE_NAME . " agab"
            . " JOIN " . AssessmentRepository::TABLE_NAME . " ab ON agab.assessmentblob_id = ab.id"
            . " WHERE agab.assessmentgroup_id = ? AND ab.uid = ?",
            [$groupId, $uid]
        );
        $this->assertNotEmpty($joins, 'the assessment should be joined to the group');
    }

    public function testUpdateAssessmentVersionForGroupRepointsToLatestVersion(): void
    {
        $uid = 'phptest-gv-' . bin2hex(random_bytes(4));
        $repo = new AssessmentRepository(new SystemLogger());
        $v1 = (int) $repo->createAssessment($uid, 'phptest Version Assessment', 'v1', [], null);
        $group = (new AssessmentGroupService())->createGroup('phptest-grp-ver-' . bin2hex(random_bytes(4)), null);
        $groupId = $group->getId();

        // the join is created pointing at v1 (the max id for the uid at insert time).
        (new AssessmentGroupService())->addAssessmentToGroup($uid, $groupId, null);

        // publish a newer version of the same uid -> higher autoincrement id.
        $v2 = (int) $repo->createAssessment($uid, 'phptest Version Assessment', 'v2', [], null);
        $this->assertGreaterThan($v1, $v2);

        $controller = new AssessmentGroupRestController();
        $response = $controller->updateAssessmentVersionForGroup($this->jsonRequest([]), $groupId);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $current = QueryUtils::fetchSingleValue(
            "SELECT assessmentblob_id FROM " . AssessmentGroupService::ASSESSMENT_BLOB_JOIN_TABLE_NAME
            . " WHERE assessmentgroup_id = ?",
            'assessmentblob_id',
            [$groupId]
        );
        $this->assertSame($v2, (int) $current, 'group join should be repointed to the newest assessment version');
    }
}
