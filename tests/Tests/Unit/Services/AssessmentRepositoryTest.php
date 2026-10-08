<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSummary;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentRepository hydration.
 *
 * Both hydrators are private and are exercised via reflection with crafted
 * records. The record always carries a raw-bytes `uuid` so the lazy
 * updateAssessmentUuid() DB write branch (triggered when uuid is empty) is
 * skipped and the method stays on the pure UuidRegistry::uuidToString() path.
 */
class AssessmentRepositoryTest extends TestCase
{
    private function newRepo(): AssessmentRepository
    {
        return new AssessmentRepository(new SystemLogger());
    }

    /**
     * @param array<string,mixed> $record
     */
    private function hydrate(AssessmentRepository $repo, array $record): AssessmentSummary
    {
        $m = new \ReflectionMethod($repo, 'hydrateAssessmentSummaryFromDatabaseRecord');
        $m->setAccessible(true);
        return $m->invoke($repo, $record);
    }

    public function testHydrateMapsScalarFieldsAndPublicFlagForNullCompany(): void
    {
        $bytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-000000000001');
        $repo = $this->newRepo();

        $summary = $this->hydrate($repo, [
            'uuid' => $bytes,
            'id' => 123,
            'uid' => 'phptest-uid',
            'name' => 'Intake Assessment',
            'description' => 'A description',
            'data' => '{"foo":"bar"}',
            'date' => '2026-01-02 03:04:05.000000',
            'company_id' => null,
        ]);

        $this->assertInstanceOf(AssessmentSummary::class, $summary);
        $this->assertSame(UuidRegistry::uuidToString($bytes), $summary->uuid);
        $this->assertSame('phptest-uid', $summary->uid);
        $this->assertSame('Intake Assessment', $summary->name);
        $this->assertSame('A description', $summary->description);
        $this->assertSame('{"foo":"bar"}', $summary->data);
        $this->assertInstanceOf(\DateTime::class, $summary->date);
        $this->assertSame('2026-01-02 03:04:05', $summary->date->format('Y-m-d H:i:s'));
        $this->assertTrue($summary->isPublic, 'null company_id => public');
    }

    public function testHydrateMarksNotPublicWhenCompanyIdPresent(): void
    {
        $bytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-000000000002');
        $summary = $this->hydrate($this->newRepo(), [
            'uuid' => $bytes,
            'id' => 7,
            'uid' => 'phptest-company',
            'name' => 'Company Assessment',
            'description' => 'scoped',
            'data' => '{}',
            'date' => '2025-12-31 23:59:59.000000',
            'company_id' => 42,
        ]);

        $this->assertFalse($summary->isPublic, 'non-empty company_id => not public');
    }

    public function testHydrateDefaultsMissingStringFieldsToEmptyString(): void
    {
        // Supply a valid date so we stay off the createFromFormat(false) crash path
        // (see latent-bug report); omit the string columns to lock in their '' default.
        $bytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-000000000003');
        $summary = $this->hydrate($this->newRepo(), [
            'uuid' => $bytes,
            'id' => 9,
            'date' => '2026-06-07 08:09:10.000000',
            'company_id' => null,
        ]);

        $this->assertSame('', $summary->uid);
        $this->assertSame('', $summary->name);
        $this->assertSame('', $summary->description);
        $this->assertSame('', $summary->data);
    }

    public function testHydratedSummarySerializesWithAtomDate(): void
    {
        $bytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-000000000004');
        $summary = $this->hydrate($this->newRepo(), [
            'uuid' => $bytes,
            'id' => 11,
            'uid' => 'phptest-serialize',
            'name' => 'N',
            'description' => 'D',
            'data' => '{"x":1}',
            'date' => '2026-01-02 03:04:05.000000',
            'company_id' => null,
        ]);

        $out = $summary->jsonSerialize();
        $this->assertSame(UuidRegistry::uuidToString($bytes), $out['uuid']);
        $this->assertSame('phptest-serialize', $out['uid']);
        $this->assertSame('{"x":1}', $out['data']);
        $this->assertTrue($out['isPublic']);
        $this->assertStringContainsString('2026-01-02', (string) $out['date']);
    }

    public function testGetAssessmentSummaryFromRecordsWrapsEachRecord(): void
    {
        $bytesA = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-00000000000a');
        $bytesB = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-00000000000b');
        $repo = $this->newRepo();

        $m = new \ReflectionMethod($repo, 'getAssessmentSummaryFromRecords');
        $m->setAccessible(true);
        /** @var AssessmentSummary[] $list */
        $list = $m->invoke($repo, [
            [
                'uuid' => $bytesA, 'id' => 1, 'uid' => 'phptest-a', 'name' => 'A',
                'description' => '', 'data' => '{}', 'date' => '2026-01-01 00:00:00.000000',
                'company_id' => null,
            ],
            [
                'uuid' => $bytesB, 'id' => 2, 'uid' => 'phptest-b', 'name' => 'B',
                'description' => '', 'data' => '{}', 'date' => '2026-01-01 00:00:00.000000',
                'company_id' => 5,
            ],
        ]);

        $this->assertCount(2, $list);
        $this->assertInstanceOf(AssessmentSummary::class, $list[0]);
        $this->assertSame('phptest-a', $list[0]->uid);
        $this->assertTrue($list[0]->isPublic);
        $this->assertSame('phptest-b', $list[1]->uid);
        $this->assertFalse($list[1]->isPublic);
    }

    public function testGetAssessmentSummaryFromRecordsReturnsEmptyForEmptyList(): void
    {
        $repo = $this->newRepo();
        $m = new \ReflectionMethod($repo, 'getAssessmentSummaryFromRecords');
        $m->setAccessible(true);
        $this->assertSame([], $m->invoke($repo, []));
    }
}
