<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignedAssessmentGroup.
 *
 * AssignedAssessmentGroup extends Assignment: fromJSON() calls parent::fromJSON()
 * then sets assessmentGroupId. jsonSerialize() merges the subclass payload with
 * the parent payload.
 */
class AssignedAssessmentGroupTest extends TestCase
{
    public function testConstructorTypeAndIsGroupType(): void
    {
        $g = new AssignedAssessmentGroup();
        $this->assertSame('AssessmentGroup', $g->getType());
        $this->assertTrue($g->isGroupType());
        $this->assertNull($g->getAssessmentGroupId());
    }

    public function testFromJsonPopulatesInheritedAndGroupId(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->fromJSON([
            'id' => 'uuid-1',
            'name' => 'Battery',
            'type' => 'AssessmentGroup',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'assessmentGroupId' => 99,
        ]);

        // inherited
        $this->assertSame('uuid-1', $g->getId());
        $this->assertSame('Battery', $g->getName());
        $this->assertSame('AssessmentGroup', $g->getType());
        $this->assertInstanceOf(\DateTime::class, $g->getDateAssigned());

        // subclass
        $this->assertSame(99, $g->getAssessmentGroupId());
    }

    public function testFromJsonGroupIdDefaultsToZeroWhenAbsent(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'AssessmentGroup']);

        $this->assertSame(0, $g->getAssessmentGroupId());
    }

    /**
     * REGRESSION (fixed v0.12.3): with no 'type' key, parent::fromJSON() no longer
     * overwrites the "AssessmentGroup" the constructor set, so isGroupType() stays true.
     */
    public function testFromJsonWithoutTypeKeyPreservesGroupType(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->fromJSON(['id' => 'x', 'name' => 'n', 'assessmentGroupId' => 1]);

        $this->assertSame('AssessmentGroup', $g->getType());
        $this->assertTrue($g->isGroupType());
    }

    public function testSetAssessmentGroupId(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->setAssessmentGroupId(7);

        $this->assertSame(7, $g->getAssessmentGroupId());
    }

    public function testJsonSerializeIncludesGroupIdAndInheritedKeys(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->fromJSON([
            'id' => 'x1',
            'name' => 'RT',
            'type' => 'AssessmentGroup',
            'assessmentGroupId' => 12,
        ]);

        $out = $g->jsonSerialize();

        $this->assertSame(12, $out['assessmentGroupId']);
        $this->assertSame('x1', $out['id']);
        $this->assertSame('AssessmentGroup', $out['type']);
        $this->assertArrayHasKey('items', $out);
        $this->assertSame([], $out['items']);
    }

    public function testJsonSerializeSerializesChildItems(): void
    {
        $g = new AssignedAssessmentGroup();
        $g->fromJSON(['id' => 'parent', 'name' => 'n', 'type' => 'AssessmentGroup', 'assessmentGroupId' => 1]);

        $child = new Assignment();
        $child->setId('child-1');
        $child->setName('Child');
        $child->setType('Assessment');
        $g->addItem($child);

        $out = $g->jsonSerialize();

        $this->assertCount(1, $out['items']);
        $this->assertSame('child-1', $out['items'][0]['id']);
    }
}
