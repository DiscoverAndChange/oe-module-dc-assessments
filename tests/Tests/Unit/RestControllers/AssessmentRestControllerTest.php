<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for AssessmentRestController against the oe-test-db harness.
 *
 * list() delegates to AssessmentRepository::getAssessmentSummaryList (status='published'
 * rows, grouped by uid); one() resolves a single published assessment by uid. Seeded
 * assessments use a 'phptest-' uid prefix and are cleaned up in tearDown.
 *
 * These are READ-path coverage: the provider UI lists assessments and reads one by uid.
 */
class AssessmentRestControllerTest extends TestCase
{
    private string $uid = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->uid = 'phptest-asmt-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid LIKE 'phptest-%'",
            [],
            true
        );
    }

    /** ServerRestRequest with an empty query string (one()/list() read no body). */
    private function request(): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getQueryParams')->willReturn([]);
        return new ServerRestRequest($inner);
    }

    private function seedAssessment(): int
    {
        $repo = new AssessmentRepository(new SystemLogger());
        return $repo->createAssessment($this->uid, 'phptest Assessment', 'desc', [], null);
    }

    public function testListReturnsSeededAssessment(): void
    {
        $this->seedAssessment();

        $response = (new AssessmentRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        // AssessmentSummary::jsonSerialize() emits the 'uid'; assert our unique uid is present.
        $this->assertStringContainsString($this->uid, $body);
    }

    public function testOneReturnsSeededAssessment(): void
    {
        $id = $this->seedAssessment();

        $response = (new AssessmentRestController())->one($this->request(), $this->uid);
        $this->assertSame(200, $response->getStatusCode());

        /** @var array<string,mixed> $body */
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('_id', $body, 'single assessment carries its row id');
        $this->assertSame((string) $id, (string) $body['_id']);
    }

    public function testOneReturns404ForUnknownUid(): void
    {
        // getAssessmentForUid() throws RECORD_NOT_FOUND (4041) for a missing uid, which
        // the controller maps to a 404 via RestUtils::getErrorResponse.
        $response = (new AssessmentRestController())->one($this->request(), 'phptest-no-such-uid');
        $this->assertSame(404, $response->getStatusCode());
    }
}
