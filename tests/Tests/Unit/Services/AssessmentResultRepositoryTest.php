<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for
 * AssessmentResultRepository::hydrateRecordsFromResult().
 *
 * The private hydrator takes raw DB rows (result_data / assessment_data JSON
 * strings) and returns plain associative arrays with the decoded result data
 * merged with the _assessment, _assignmentItemId and _dateCompleted keys. It
 * performs no DB access, so it is invoked directly via reflection.
 */
class AssessmentResultRepositoryTest extends TestCase
{
    /**
     * @param list<array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function hydrate(array $rows): array
    {
        $repo = new AssessmentResultRepository();
        $m = new \ReflectionMethod($repo, 'hydrateRecordsFromResult');
        $m->setAccessible(true);
        return $m->invoke($repo, $rows);
    }

    public function testHydrateDecodesBlobsAndMergesMetaKeys(): void
    {
        $out = $this->hydrate([
            [
                'result_data' => '{"_answers":[{"_score":5}],"foo":"bar"}',
                'assessment_data' => '{"name":"Assess"}',
                'assignmentitem_id' => 42,
                'date' => '2026-01-02 03:04:05',
            ],
        ]);

        $this->assertCount(1, $out);
        $record = $out[0];
        $this->assertSame('bar', $record['foo']);
        $this->assertSame([['_score' => 5]], $record['_answers']);
        $this->assertSame(['name' => 'Assess'], $record['_assessment']);
        $this->assertSame(42, $record['_assignmentItemId']);
        $this->assertSame('2026-01-02 03:04:05', $record['_dateCompleted']);
    }

    public function testHydrateDefaultsEmptyBlobsToEmptyArrays(): void
    {
        // result_data / assessment_data absent => null-coalesce to '{}' => [].
        $out = $this->hydrate([
            [
                'assignmentitem_id' => null,
                'date' => '2026-05-05 05:05:05',
            ],
        ]);

        $this->assertCount(1, $out);
        $record = $out[0];
        $this->assertSame([], $record['_assessment']);
        $this->assertNull($record['_assignmentItemId'], 'LEFT JOIN miss => null assignment item id');
        $this->assertSame('2026-05-05 05:05:05', $record['_dateCompleted']);
        // result_data was absent, so the only keys are the three injected meta keys.
        $this->assertSame(['_assessment', '_assignmentItemId', '_dateCompleted'], array_keys($record));
    }

    public function testHydratePreservesRowOrderAcrossMultipleRecords(): void
    {
        $out = $this->hydrate([
            [
                'result_data' => '{"n":1}',
                'assessment_data' => '{}',
                'assignmentitem_id' => 100,
                'date' => '2026-01-01 00:00:00',
            ],
            [
                'result_data' => '{"n":2}',
                'assessment_data' => '{}',
                'assignmentitem_id' => 200,
                'date' => '2026-02-02 00:00:00',
            ],
        ]);

        $this->assertCount(2, $out);
        $this->assertSame(1, $out[0]['n']);
        $this->assertSame(100, $out[0]['_assignmentItemId']);
        $this->assertSame(2, $out[1]['n']);
        $this->assertSame(200, $out[1]['_assignmentItemId']);
    }

    public function testHydrateReturnsEmptyArrayForNoRows(): void
    {
        $this->assertSame([], $this->hydrate([]));
    }

    /**
     * REGRESSION (fixed v0.12.3): result_data that decodes to a non-array (a JSON
     * scalar/list, or null from invalid JSON) previously broke the associative-array
     * merge below. Such payloads are now normalized to an empty array so the meta
     * keys still attach cleanly.
     */
    public function testHydrateNormalizesNonArrayResultData(): void
    {
        $out = $this->hydrate([
            ['result_data' => '42', 'assessment_data' => '{}', 'assignmentitem_id' => 7, 'date' => '2026-03-03 03:03:03'],
            ['result_data' => 'not valid json', 'assessment_data' => '{}', 'assignmentitem_id' => 8, 'date' => '2026-04-04 04:04:04'],
        ]);

        $this->assertCount(2, $out);
        $this->assertSame(['_assessment', '_assignmentItemId', '_dateCompleted'], array_keys($out[0]));
        $this->assertSame(7, $out[0]['_assignmentItemId']);
        $this->assertSame(8, $out[1]['_assignmentItemId']);
    }

    /**
     * REGRESSION (fixed v0.12.3): assignmentitem_id / date are read with a null
     * coalesce, so an absent key no longer raises an undefined-key warning.
     */
    public function testHydrateToleratesMissingMetaKeys(): void
    {
        $out = $this->hydrate([
            ['result_data' => '{"n":1}', 'assessment_data' => '{}'],
        ]);

        $this->assertCount(1, $out);
        $this->assertNull($out[0]['_assignmentItemId']);
        $this->assertNull($out[0]['_dateCompleted']);
    }
}
