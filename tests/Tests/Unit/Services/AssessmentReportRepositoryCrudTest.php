<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentReportRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed CRUD tests for AssessmentReportRepository against the oe-test-db harness.
 *
 * Covers the public surface: createReport, getOne, getAll, updateReport (and the
 * getOne-returns-null fall-through for a missing id). All seeded rows use a
 * 'phptest-' id prefix and are removed in tearDown(). User id 1 ('system') is the
 * seeded user referenced by the created_by / last_updated_by FKs.
 */
class AssessmentReportRepositoryCrudTest extends TestCase
{
    private const USER_ID = 1;

    private AssessmentReportRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AssessmentReportRepository();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // children before parents (version/permission FK -> dac_Report.id)
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_VERSION_NAME . " WHERE report_id LIKE 'phptest%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_PERMISSION_NAME . " WHERE report_id LIKE 'phptest%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentReportRepository::TABLE_NAME . " WHERE id LIKE 'phptest%'",
            [],
            true
        );
    }

    public function testCreateReportThenGetOneReturnsData(): void
    {
        $id = 'phptest-report-getone';
        $this->repo->createReport(
            $id,
            'phptest Report GetOne',
            self::USER_ID,
            ['id' => $id, 'foo' => 'bar', 'token' => 'should-be-stripped'],
            null,
            null
        );

        $report = $this->repo->getOne($id);
        $this->assertIsArray($report, 'getOne() should return the stored report data');
        $this->assertSame('bar', $report['foo']);
        $this->assertSame($id, $report['id']);
        $this->assertArrayNotHasKey('token', $report, 'createReport() strips the carryover token');
        // no group / assessment linkage was supplied
        $this->assertArrayNotHasKey('linkedGroup', $report);
        $this->assertArrayNotHasKey('linkedAssessments', $report);
    }

    public function testGetOneReturnsNullForMissingId(): void
    {
        $this->assertNull(
            $this->repo->getOne('phptest-report-does-not-exist'),
            'getOne() for an unknown id falls through to null'
        );
    }

    public function testExistsReportReflectsCreation(): void
    {
        $id = 'phptest-report-exists';
        $this->assertFalse($this->repo->existsReport($id));
        $this->repo->createReport($id, 'phptest Report Exists', self::USER_ID, ['id' => $id], null, null);
        $this->assertTrue($this->repo->existsReport($id));
    }

    public function testGetAllIncludesCreatedReport(): void
    {
        $id = 'phptest-report-getall';
        $this->repo->createReport(
            $id,
            'phptest Report GetAll',
            self::USER_ID,
            ['id' => $id, 'marker' => 'phptest-marker'],
            null,
            null
        );

        $all = $this->repo->getAll(true);
        $this->assertIsArray($all);
        $match = null;
        foreach ($all as $r) {
            if (($r['id'] ?? null) === $id) {
                $match = $r;
                break;
            }
        }
        $this->assertNotNull($match, 'getAll(true) should include the newly created report');
        $this->assertSame('phptest-marker', $match['marker']);
    }

    public function testUpdateReportCreatesNewVersionAndGetOneReflectsIt(): void
    {
        $id = 'phptest-report-update';
        $this->repo->createReport(
            $id,
            'phptest Report Update',
            self::USER_ID,
            ['id' => $id, 'foo' => 'original'],
            null,
            null
        );

        $versionId = $this->repo->updateReport(
            $id,
            'phptest Report Update v2',
            self::USER_ID,
            ['id' => $id, 'foo' => 'updated', 'token' => 'strip-me'],
            null,
            null
        );
        $this->assertGreaterThan(0, $versionId, 'updateReport() returns the new version row id');

        $report = $this->repo->getOne($id);
        $this->assertIsArray($report);
        $this->assertSame('updated', $report['foo'], 'getOne() returns the latest version payload');
        $this->assertArrayNotHasKey('token', $report, 'updateReport() strips the carryover token');
    }

    public function testUpdateReportThrowsForMissingId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repo->updateReport(
            'phptest-report-missing-update',
            'phptest Missing',
            self::USER_ID,
            ['id' => 'phptest-report-missing-update'],
            null,
            null
        );
    }
}
