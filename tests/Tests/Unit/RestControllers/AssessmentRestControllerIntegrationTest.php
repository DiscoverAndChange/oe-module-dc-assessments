<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AclIntegration.php';

/**
 * DB-backed integration test for the ACL-gated AssessmentRestController write actions
 * (create -> createAssessmentForContext insert, and update).
 *
 * loginAsAdmin() installs the ACL tree + logs in the seeded admin so
 * AclMain::aclCheckCore('admin','forms') passes and getAuthRole() resolves to
 * Role::SuperUser (companyId=null path; canEditAssessment() then short-circuits true).
 * Without the trait both actions only ever return an access-denied response.
 *
 * Seeded assessment rows use a phptest% uid prefix and are removed in tearDown, so the
 * suite is independent of pre-existing data.
 */
class AssessmentRestControllerIntegrationTest extends TestCase
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
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid LIKE 'phptest%'",
            [],
            true
        );
    }

    public function testCreatePersistsAssessment(): void
    {
        $uid = 'phptest-c-' . bin2hex(random_bytes(4)); // <= 32 chars for the _uid rule
        $controller = new AssessmentRestController(new SystemLogger());

        $response = $controller->create($this->jsonRequest([
            '_uid' => $uid,
            '_name' => 'phptest Created Assessment',
            '_description' => 'phptest description',
        ]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        // getAssessmentForUid throws RECORD_NOT_FOUND if the published row is absent.
        $persisted = (new AssessmentRepository(new SystemLogger()))->getAssessmentForUid($uid);
        $this->assertNotEmpty($persisted, 'created assessment should be persisted and published');
    }

    public function testCreateWithMissingFieldsReturns400(): void
    {
        // missing _name/_description -> DATABASE_INSERT_CONTEXT validation fails -> 400.
        $controller = new AssessmentRestController(new SystemLogger());
        $response = $controller->create($this->jsonRequest(['_uid' => 'phptest-x']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateDuplicateUidReturnsError(): void
    {
        $uid = 'phptest-d-' . bin2hex(random_bytes(4));
        (new AssessmentRepository(new SystemLogger()))
            ->createAssessment($uid, 'phptest Existing', 'desc', [], null);

        $controller = new AssessmentRestController(new SystemLogger());
        $response = $controller->create($this->jsonRequest([
            '_uid' => $uid,
            '_name' => 'phptest Duplicate',
            '_description' => 'phptest dup description',
        ]));

        // insert context + existsAssessment() -> DUP_ENTRY, surfaced as a non-200 error.
        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function testUpdatePersistsNewVersion(): void
    {
        $uid = 'phptest-u-' . bin2hex(random_bytes(4));
        $repo = new AssessmentRepository(new SystemLogger());
        $id = (int) $repo->createAssessment($uid, 'phptest Original Name', 'orig', [], null);

        $controller = new AssessmentRestController(new SystemLogger());
        $response = $controller->update($this->jsonRequest([
            '_uid' => $uid,
            '_name' => 'phptest Updated Name',
            '_description' => 'phptest updated description',
        ]), (string) $id);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        // update writes a new version row for the same uid; assert the updated copy landed.
        $count = QueryUtils::fetchSingleValue(
            "SELECT COUNT(*) AS cnt FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid = ? AND name = ?",
            'cnt',
            [$uid, 'phptest Updated Name']
        );
        $this->assertGreaterThan(0, (int) $count, 'updated assessment version should be persisted');
    }
}
