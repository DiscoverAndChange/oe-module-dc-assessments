<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignedAssessment.
 *
 * AssignedAssessment extends Assignment: fromJSON() calls parent::fromJSON()
 * (inherited id/name/type/dates/appointmentId) and then sets the subclass
 * fields (assessmentId/uid/assessmentUuid/resultId/itemId). These lock in the
 * current array<->object behavior ahead of the typing refactor.
 */
class AssignedAssessmentTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $a = new AssignedAssessment();
        $this->assertSame('Assessment', $a->getType());
        $this->assertSame('', $a->getUid());
        $this->assertSame('', $a->getAssessmentUuid());
        $this->assertNull($a->getResultId());
        $this->assertNull($a->getItemId());
        $this->assertFalse($a->getIsComplete());
    }

    public function testFromJsonPopulatesInheritedAndSubclassFields(): void
    {
        $a = new AssignedAssessment();
        $a->fromJSON([
            'id' => 'uuid-1',
            'name' => 'PHQ-9',
            'type' => 'Assessment',
            'appointmentId' => '7',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'assessmentId' => 42,
            'uid' => 'u-abc',
            'assessmentUuid' => 'ass-uuid-9',
            'resultId' => 'res-55',
            'itemId' => 13,
        ]);

        // inherited (parent::fromJSON)
        $this->assertSame('uuid-1', $a->getId());
        $this->assertSame('PHQ-9', $a->getName());
        $this->assertSame('Assessment', $a->getType());
        $this->assertSame('7', $a->getAppointmentId());
        $this->assertInstanceOf(\DateTime::class, $a->getDateAssigned());
        $this->assertSame('2026-01-02', $a->getDateAssigned()->format('Y-m-d'));

        // subclass
        $this->assertSame(42, $a->getAssessmentId());
        $this->assertSame('u-abc', $a->getUid());
        $this->assertSame('ass-uuid-9', $a->getAssessmentUuid());
        $this->assertSame('res-55', $a->getResultId());
        $this->assertSame(13, $a->getItemId());

        // resultId present => setResultId stamps dateCompleted => complete
        $this->assertTrue($a->getIsComplete());
        $this->assertInstanceOf(\DateTime::class, $a->getDateCompleted());
    }

    public function testFromJsonWithoutResultIdLeavesIncomplete(): void
    {
        $a = new AssignedAssessment();
        $a->fromJSON([
            'id' => 'uuid-2',
            'name' => 'n',
            'type' => 'Assessment',
            'assessmentId' => 1,
            'uid' => 'u',
            'assessmentUuid' => 'au',
        ]);

        $this->assertNull($a->getResultId());
        $this->assertFalse($a->getIsComplete());
        $this->assertNull($a->getDateCompleted());
    }

    public function testFromJsonItemIdDefaultsToZeroWhenAbsent(): void
    {
        $a = new AssignedAssessment();
        $a->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'Assessment', 'assessmentId' => 2]);

        $this->assertSame(0, $a->getItemId());
    }

    public function testSetResultIdMarksCompleteAndIsFluentlyChainable(): void
    {
        $a = new AssignedAssessment();
        $a->setResultId('r-1');

        $this->assertSame('r-1', $a->getResultId());
        $this->assertTrue($a->getIsComplete());
        $this->assertInstanceOf(\DateTime::class, $a->getDateCompleted());
    }

    public function testJsonSerializeIncludesInheritedAndSubclassKeys(): void
    {
        $a = new AssignedAssessment();
        $a->fromJSON([
            'id' => 'x1',
            'name' => 'Round trip',
            'type' => 'Assessment',
            'assessmentId' => 99,
            'uid' => 'uid-9',
            'assessmentUuid' => 'au-9',
            'resultId' => 'res-9',
            'itemId' => 4,
            'dateAssigned' => '2026-05-06T07:08:09.000000+00:00',
        ]);

        $out = $a->jsonSerialize();

        // inherited keys
        $this->assertSame('x1', $out['id']);
        $this->assertSame('Round trip', $out['name']);
        $this->assertSame('Assessment', $out['type']);
        $this->assertStringContainsString('2026-05-06', (string) $out['dateAssigned']);

        // subclass keys
        $this->assertSame(99, $out['assessmentId']);
        $this->assertSame('uid-9', $out['uid']);
        $this->assertSame('au-9', $out['assessmentUuid']);
        $this->assertSame('res-9', $out['resultId']);
        $this->assertSame(4, $out['itemId']);
    }
}
