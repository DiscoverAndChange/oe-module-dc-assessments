<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentReportRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentReportRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for AssessmentReportRestController against the oe-test-db harness.
 *
 * list() returns every current report version whose permission row has show=1 (createReport
 * seeds show=1); one() returns the single report's decoded JSON payload by id. Seeded reports
 * use a 'phptest-' id prefix and are cleaned up (children first, respecting FKs) in tearDown.
 *
 * NOTE (reported, not fixed): one() for an UNKNOWN id does NOT return 404 — getOne() returns
 * null and the controller passes it to RestUtils::returnSingleObjectResponse(), which always
 * emits 200 (body "null"); the trailing getNotFoundResponse() is dead code. The unknown-id
 * test below asserts that actual 200/null behaviour.
 */
class AssessmentReportRestControllerTest extends TestCase
{
    private string $reportId = '';
    private string $marker = '';

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = bin2hex(random_bytes(4));
        $this->reportId = 'phptest-report-' . $suffix;
        $this->marker = 'phptest-marker-' . $suffix;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // FK order: version + permission reference dac_Report, so delete them first.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_VERSION_NAME . " WHERE report_id LIKE 'phptest-%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_PERMISSION_NAME . " WHERE report_id LIKE 'phptest-%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_NAME . " WHERE id LIKE 'phptest-%'",
            [],
            true
        );
    }

    private function request(): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getQueryParams')->willReturn([]);
        return new ServerRestRequest($inner);
    }

    private function seedReport(): void
    {
        // The report payload ($data) is stored verbatim as the version JSON and echoed back
        // by both getAll() and getOne(); embed a unique marker so we can find it in the body.
        $data = ['id' => $this->reportId, 'name' => $this->marker, 'phptest' => true];
        (new AssessmentReportRepository())->createReport($this->reportId, $this->marker, 1, $data, null, null);
    }

    public function testListReturnsSeededReport(): void
    {
        $this->seedReport();

        $response = (new AssessmentReportRestController(new SystemLogger()))->list($this->request());
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertIsArray(json_decode($body, true));
        $this->assertStringContainsString($this->marker, $body);
    }

    public function testOneReturnsSeededReport(): void
    {
        $this->seedReport();

        $response = (new AssessmentReportRestController(new SystemLogger()))->one($this->request(), $this->reportId);
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertStringContainsString($this->marker, $body);
    }

    /**
     * REGRESSION (fixed v0.12.4): one() for an unknown id previously passed getOne()'s null
     * to returnSingleObjectResponse() and emitted a 200 with a "null" body (the trailing
     * getNotFoundResponse() was unreachable). It now returns a proper 404.
     */
    public function testOneForUnknownIdReturns404(): void
    {
        $response = (new AssessmentReportRestController(new SystemLogger()))->one($this->request(), 'phptest-no-such-report');
        $this->assertSame(404, $response->getStatusCode());
    }
}
