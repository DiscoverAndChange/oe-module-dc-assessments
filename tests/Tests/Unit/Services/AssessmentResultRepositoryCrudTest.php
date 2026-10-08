<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed CRUD tests for AssessmentResultRepository against the oe-test-db harness.
 *
 * Exercises the public write path (createResult) and the public read paths
 * (getResultsForPatient / getResultListForPatient) end to end: a result blob is
 * created against a seeded patient + assessment, then read back and asserted. The
 * private hydrateRecordsFromResult() is already covered by AssessmentResultRepositoryTest.
 *
 * Seed data (patient_data, dac_AssessmentBlob, dac_AssessmentResultBlob) all use a
 * 'phptest-' prefix and are removed in tearDown(), children before parents.
 */
class AssessmentResultRepositoryCrudTest extends TestCase
{
    private AssessmentResultRepository $repo;

    /** patient_data.id (== pid), used as the result blob client_id */
    private int $pid;

    /** string uuid of the seeded patient */
    private string $patientUuid;

    /** dac_AssessmentBlob.id of the seeded assessment */
    private int $assessmentId;

    /** dac_AssessmentBlob.uid of the seeded assessment */
    private string $assessmentUid = 'phptest-ar-uid';

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AssessmentResultRepository();

        // Seed a patient. pid is forced equal to id so the repository joins
        // (arb.client_id = c.pid) line up with the client_id -> patient_data.id FK.
        $this->pid = (int) QueryUtils::sqlInsert(
            "INSERT INTO patient_data (fname, lname, email, pubpid) VALUES (?, ?, ?, ?)",
            ['phptest-arfname', 'phptest-arlname', 'phptest-ar@example.test', 'phptest-ar-pub']
        );
        QueryUtils::sqlStatementThrowException(
            "UPDATE patient_data SET pid = ? WHERE id = ?",
            [$this->pid, $this->pid]
        );
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        $uuidBytes = QueryUtils::fetchSingleValue(
            "SELECT uuid FROM patient_data WHERE id = ?",
            'uuid',
            [$this->pid]
        );
        $this->patientUuid = UuidRegistry::uuidToString($uuidBytes);

        // Seed an assessment so result reads can JOIN dac_AssessmentBlob.
        $assessmentRepo = new AssessmentRepository(new SystemLogger());
        $this->assessmentId = (int) $assessmentRepo->createAssessment(
            $this->assessmentUid,
            'phptest-ar-assessment',
            'phptest-ar-assessment',
            [],
            null
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentResultRepository::TABLE_NAME . " WHERE id LIKE 'phptest%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . AssessmentRepository::TABLE_NAME . " WHERE uid LIKE 'phptest%'",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM patient_data WHERE fname LIKE 'phptest%'",
            [],
            true
        );
    }

    /** @return array<string,mixed> */
    private function createResult(string $resultId): array
    {
        return $this->repo->createResult(
            $resultId,
            [
                '_answers' => [
                    ['_answer' => 'phptest answer', '_score' => 3, '_question_id' => 'q1'],
                ],
                'data' => ['title' => 'phptest-result-title'],
            ],
            $this->pid,
            $this->assessmentId
        );
    }

    public function testCreateResultReturnsRowDescriptor(): void
    {
        $resultId = 'phptest-result-create';
        $row = $this->createResult($resultId);

        $this->assertSame($resultId, $row['id']);
        $this->assertSame($this->assessmentId, $row['assessment_id']);
        $this->assertSame($this->pid, $row['client_id']);
        // the stored blob is resultData['data'] with the server id injected
        $stored = json_decode((string) $row['data'], true);
        $this->assertSame($resultId, $stored['_id'], 'createResult() injects the server id into the data blob');
        $this->assertSame('phptest-result-title', $stored['title']);
    }

    public function testGetResultsForPatientByAssessmentUid(): void
    {
        $resultId = 'phptest-result-byuid';
        $this->createResult($resultId);

        $record = $this->repo->getResultsForPatient($this->patientUuid, $this->assessmentUid, null);
        $this->assertIsArray($record, 'result should be retrievable by assessment uid + patient uuid');
        $this->assertSame($resultId, $record['_id']);
        $this->assertSame('phptest-result-title', $record['title']);
        $this->assertArrayHasKey('_assessment', $record, 'hydrated record carries the assessment payload');
    }

    public function testGetResultsForPatientByResultId(): void
    {
        $resultId = 'phptest-result-byid';
        $this->createResult($resultId);

        $record = $this->repo->getResultsForPatient($this->patientUuid, null, $resultId);
        $this->assertIsArray($record);
        $this->assertSame($resultId, $record['_id']);
    }

    public function testGetResultListForPatientReturnsCreatedResult(): void
    {
        $resultId = 'phptest-result-list';
        $this->createResult($resultId);

        $records = $this->repo->getResultListForPatient($this->patientUuid, [$resultId]);
        $this->assertIsArray($records);
        $this->assertCount(1, $records);
        $this->assertSame($resultId, $records[0]['_id']);
    }

    public function testGetResultsForPatientReturnsNullForUnknownAssessment(): void
    {
        $this->assertNull(
            $this->repo->getResultsForPatient($this->patientUuid, 'phptest-no-such-uid', null)
        );
    }

    public function testGetResultListForPatientReturnsNullForUnknownResultId(): void
    {
        $this->assertNull(
            $this->repo->getResultListForPatient($this->patientUuid, ['phptest-no-such-result'])
        );
    }

    public function testGetResultsForPatientThrowsWhenNoSelector(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repo->getResultsForPatient($this->patientUuid, null, null);
    }

    public function testGetResultListForPatientThrowsForEmptyIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repo->getResultListForPatient($this->patientUuid, []);
    }
}
