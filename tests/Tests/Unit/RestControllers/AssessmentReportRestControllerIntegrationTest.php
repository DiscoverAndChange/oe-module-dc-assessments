<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentReportRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentReportRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AclIntegration.php';

/**
 * DB-backed integration test for the ACL-gated AssessmentReportRestController write actions.
 * loginAsAdmin() installs the ACL tree + logs in the seeded admin so aclCheckCore() passes
 * and the create/update bodies actually run. Seeded rows use a phptest% prefix.
 */
class AssessmentReportRestControllerIntegrationTest extends TestCase
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
        QueryUtils::sqlStatementThrowException("DELETE FROM " . AssessmentReportRepository::TABLE_VERSION_NAME . " WHERE report_id LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM " . AssessmentReportRepository::TABLE_PERMISSION_NAME . " WHERE report_id LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM " . AssessmentReportRepository::TABLE_NAME . " WHERE id LIKE 'phptest%'", [], true);
    }

    public function testCreatePersistsReport(): void
    {
        $controller = new AssessmentReportRestController(new SystemLogger());
        $request = $this->jsonRequest([
            'id' => 'phptest-int-report-1',
            'name' => 'phptest Integration Report',
            'variables' => [],
            'computedResults' => [],
        ]);

        $response = $controller->create($request);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotEmpty((new AssessmentReportRepository())->getOne('phptest-int-report-1'), 'report should be persisted');
    }

    public function testUpdatePersistsNewVersion(): void
    {
        $repo = new AssessmentReportRepository();
        $repo->createReport('phptest-int-report-2', 'phptest Original', self::ACL_USER_ID, ['id' => 'phptest-int-report-2', 'name' => 'phptest Original'], null, null);

        $controller = new AssessmentReportRestController(new SystemLogger());
        $response = $controller->update($this->jsonRequest([
            'id' => 'phptest-int-report-2',
            'name' => 'phptest Updated',
            'variables' => [],
            'computedResults' => [],
        ]), 'phptest-int-report-2');

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $report = $repo->getOne('phptest-int-report-2');
        $this->assertNotEmpty($report);
    }

    public function testCreateWithInvalidBodyReturns400(): void
    {
        $controller = new AssessmentReportRestController(new SystemLogger());
        // missing required id/name -> validator fails -> VALIDATION_FAILED (400)
        $response = $controller->create($this->jsonRequest(['variables' => []]));

        $this->assertSame(400, $response->getStatusCode());
    }
}
