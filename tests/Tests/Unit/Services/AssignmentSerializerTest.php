<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignmentSerializer.
 *
 * AssignmentSerializer::serialize() simply delegates to Assignment::jsonSerialize().
 * deserialize() routes on the payload's "type" key:
 *
 *   - "AssessmentGroup": the returned top-level object is a concrete
 *     AssignedAssessmentGroup, and every entry in "items" is recursively
 *     deserialized and attached.
 *   - "Assessment" / "Questionnaire" / "LibraryAsset" (leaf types): the returned
 *     top-level object is a PLAIN Assignment (NOT the concrete subclass). The
 *     concrete subclass (AssignedAssessment/AssignedQuestionnaire/AssignedLibraryAsset)
 *     is only created and attached as items[0], and ONLY when the payload carries a
 *     non-empty "items" array. With no items, the concrete sub-item is discarded.
 *     Only the FIRST item is consulted for leaf types.
 *   - "TemplateProfile": NOT handled, even though it is a valid Assignment model type
 *     (Assignment::ASSIGNMENT_TYPES). deserialize() throws InvalidArgumentException.
 *   - any other / null / missing type: throws InvalidArgumentException.
 *
 * These tests pin down the behavior as it exists today; see the surprising-behavior
 * notes on individual methods.
 */
class AssignmentSerializerTest extends TestCase
{
    private function serializer(): AssignmentSerializer
    {
        return new AssignmentSerializer();
    }

    public function testSerializeDelegatesToJsonSerialize(): void
    {
        $group = new AssignedAssessmentGroup();
        $group->fromJSON(['id' => 'g-1', 'name' => 'Battery', 'type' => 'AssessmentGroup', 'assessmentGroupId' => 7]);

        $this->assertSame($group->jsonSerialize(), $this->serializer()->serialize($group));
    }

    public function testDeserializeAssessmentGroupReturnsConcreteGroup(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'g-1',
            'name' => 'Battery',
            'type' => 'AssessmentGroup',
            'assessmentGroupId' => 42,
        ]);

        $this->assertInstanceOf(AssignedAssessmentGroup::class, $assignment);
        $this->assertSame('g-1', $assignment->getId());
        $this->assertSame('AssessmentGroup', $assignment->getType());
        $this->assertSame(42, $assignment->getAssessmentGroupId());
        $this->assertTrue($assignment->isGroupType());
        $this->assertSame([], $assignment->getItems());
    }

    /**
     * Characterizes the surprising leaf-type shape: the top-level object is a plain
     * Assignment and the concrete AssignedAssessment lives at items[0].
     */
    public function testDeserializeAssessmentLeafTopLevelIsPlainAssignmentWithConcreteChild(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'a-1',
            'name' => 'PHQ-9',
            'type' => 'Assessment',
            'items' => [
                [
                    'id' => 'a-1',
                    'name' => 'PHQ-9',
                    'type' => 'Assessment',
                    'assessmentId' => 5,
                    'uid' => 'u-1',
                    'assessmentUuid' => 'au-1',
                    'itemId' => 3,
                    'resultId' => null,
                ],
            ],
        ]);

        // top-level is a plain Assignment, not the concrete subclass
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertNotInstanceOf(AssignedAssessment::class, $assignment);
        $this->assertSame('a-1', $assignment->getId());
        $this->assertSame('Assessment', $assignment->getType());

        // the concrete subclass is the single child
        $items = $assignment->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(AssignedAssessment::class, $items[0]);
        $this->assertSame(5, $items[0]->getAssessmentId());
        $this->assertSame('u-1', $items[0]->getUid());
        $this->assertSame('au-1', $items[0]->getAssessmentUuid());
        $this->assertSame(3, $items[0]->getItemId());
    }

    public function testDeserializeQuestionnaireLeafChildIsConcrete(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'q-1',
            'name' => 'Intake',
            'type' => 'Questionnaire',
            'items' => [
                ['id' => 'q-1', 'name' => 'Intake', 'type' => 'Questionnaire', 'questionnaireId' => 'qq-9'],
            ],
        ]);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertNotInstanceOf(AssignedQuestionnaire::class, $assignment);
        $this->assertSame('Questionnaire', $assignment->getType());

        $items = $assignment->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(AssignedQuestionnaire::class, $items[0]);
        $this->assertSame('qq-9', $items[0]->getQuestionnaireId());
    }

    public function testDeserializeLibraryAssetLeafChildIsConcrete(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'l-1',
            'name' => 'Handout',
            'type' => 'LibraryAsset',
            'items' => [
                ['id' => 'l-1', 'name' => 'Handout', 'type' => 'LibraryAsset', 'assetId' => 11, 'assetUuid' => 'as-uuid'],
            ],
        ]);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertNotInstanceOf(AssignedLibraryAsset::class, $assignment);
        $this->assertSame('LibraryAsset', $assignment->getType());

        $items = $assignment->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(AssignedLibraryAsset::class, $items[0]);
        $this->assertSame(11, $items[0]->getAssetId());
        $this->assertSame('as-uuid', $items[0]->getAssetUuid());
    }

    /**
     * Surprising behavior: a leaf payload with no "items" yields a plain Assignment
     * with an EMPTY items list. The concrete sub-item that was constructed is silently
     * discarded, so none of the leaf-specific fields survive deserialization.
     */
    public function testDeserializeLeafWithoutItemsDropsConcreteSubItem(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'a-1',
            'name' => 'PHQ-9',
            'type' => 'Assessment',
            'assessmentId' => 5,
        ]);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertNotInstanceOf(AssignedAssessment::class, $assignment);
        $this->assertSame('Assessment', $assignment->getType());
        $this->assertSame([], $assignment->getItems());
    }

    /**
     * Surprising behavior: for leaf types only items[0] is consulted; additional
     * items are ignored.
     */
    public function testDeserializeLeafUsesOnlyFirstItem(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'a-1',
            'name' => 'first',
            'type' => 'Assessment',
            'items' => [
                ['id' => 'a-1', 'type' => 'Assessment', 'assessmentId' => 1],
                ['id' => 'a-2', 'type' => 'Assessment', 'assessmentId' => 2],
            ],
        ]);

        $items = $assignment->getItems();
        $this->assertCount(1, $items);
        $this->assertSame(1, $items[0]->getAssessmentId());
    }

    /**
     * A group with nested children round-trips: every child is recursively
     * deserialized and preserved (unlike leaf types, groups walk the whole list).
     */
    public function testDeserializeNestedGroupPreservesAllChildren(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'g-1',
            'name' => 'Outer',
            'type' => 'AssessmentGroup',
            'assessmentGroupId' => 1,
            'items' => [
                ['id' => 'g-2', 'name' => 'Inner', 'type' => 'AssessmentGroup', 'assessmentGroupId' => 2, 'items' => []],
                [
                    'id' => 'a-1',
                    'name' => 'Leaf',
                    'type' => 'Assessment',
                    'items' => [
                        ['id' => 'a-1', 'type' => 'Assessment', 'assessmentId' => 7, 'uid' => 'u', 'assessmentUuid' => 'au'],
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(AssignedAssessmentGroup::class, $assignment);
        $items = $assignment->getItems();
        $this->assertCount(2, $items);

        // nested group child stays a concrete group
        $this->assertInstanceOf(AssignedAssessmentGroup::class, $items[0]);
        $this->assertSame('g-2', $items[0]->getId());
        $this->assertSame(2, $items[0]->getAssessmentGroupId());

        // leaf child is a plain Assignment wrapping a concrete AssignedAssessment grandchild
        $this->assertInstanceOf(Assignment::class, $items[1]);
        $this->assertNotInstanceOf(AssignedAssessment::class, $items[1]);
        $this->assertSame('Assessment', $items[1]->getType());
        $grandchildren = $items[1]->getItems();
        $this->assertCount(1, $grandchildren);
        $this->assertInstanceOf(AssignedAssessment::class, $grandchildren[0]);
        $this->assertSame(7, $grandchildren[0]->getAssessmentId());
    }

    /**
     * Exercises (via the serializer) the AssignedQuestionnaire::fromJSON fix that now
     * hydrates resultId / documentId / documentTemplateId.
     */
    public function testDeserializeQuestionnaireChildHydratesResultAndDocumentFields(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'q-1',
            'name' => 'Intake',
            'type' => 'Questionnaire',
            'items' => [
                [
                    'id' => 'q-1',
                    'type' => 'Questionnaire',
                    'questionnaireId' => 'qq-9',
                    'resultId' => 'res-1',
                    'documentId' => 'doc-1',
                    'documentTemplateId' => 4,
                ],
            ],
        ]);

        $child = $assignment->getItems()[0];
        $this->assertInstanceOf(AssignedQuestionnaire::class, $child);
        $this->assertSame('res-1', $child->getResultId());
        $this->assertSame('doc-1', $child->getDocumentId());
        $this->assertSame(4, $child->getDocumentTemplateId());
        $this->assertTrue($child->getIsComplete());
    }

    /**
     * Exercises (via the serializer) the Assignment::fromJSON fix: an item payload with
     * no "type" key no longer resets the constructor-set type back to "Assessment".
     */
    public function testDeserializeQuestionnaireChildPreservesTypeWhenItemHasNoTypeKey(): void
    {
        $assignment = $this->serializer()->deserialize([
            'id' => 'q-1',
            'name' => 'Intake',
            'type' => 'Questionnaire',
            'items' => [
                ['id' => 'q-1', 'name' => 'Intake', 'questionnaireId' => 'qq-9'],
            ],
        ]);

        $child = $assignment->getItems()[0];
        $this->assertInstanceOf(AssignedQuestionnaire::class, $child);
        $this->assertSame('Questionnaire', $child->getType());
        $this->assertSame('qq-9', $child->getQuestionnaireId());
    }

    public function testRoundTripAssessmentGroupPreservesKeyFields(): void
    {
        $input = [
            'id' => 'g-1',
            'name' => 'Battery',
            'type' => 'AssessmentGroup',
            'assessmentGroupId' => 42,
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'items' => [
                ['id' => 'g-2', 'name' => 'Inner', 'type' => 'AssessmentGroup', 'assessmentGroupId' => 2, 'items' => []],
                [
                    'id' => 'a-1',
                    'name' => 'Leaf',
                    'type' => 'Assessment',
                    'items' => [
                        ['id' => 'a-1', 'type' => 'Assessment', 'assessmentId' => 7, 'uid' => 'u', 'assessmentUuid' => 'au', 'itemId' => 1, 'resultId' => null],
                    ],
                ],
            ],
        ];

        $serializer = $this->serializer();
        $deserialized = $serializer->deserialize($input);
        $this->assertInstanceOf(\DateTime::class, $deserialized->getDateAssigned());

        $out = $serializer->serialize($deserialized);

        $this->assertSame('g-1', $out['id']);
        $this->assertSame('Battery', $out['name']);
        $this->assertSame('AssessmentGroup', $out['type']);
        $this->assertSame(42, $out['assessmentGroupId']);
        $this->assertCount(2, $out['items']);
        $this->assertSame('g-2', $out['items'][0]['id']);
        $this->assertSame(2, $out['items'][0]['assessmentGroupId']);
        $this->assertSame('a-1', $out['items'][1]['id']);
        $this->assertSame('Assessment', $out['items'][1]['type']);
        // the leaf's concrete detail survives as the grandchild item
        $this->assertSame(7, $out['items'][1]['items'][0]['assessmentId']);
        $this->assertSame('u', $out['items'][1]['items'][0]['uid']);
        $this->assertSame('au', $out['items'][1]['items'][0]['assessmentUuid']);
        $this->assertSame(1, $out['items'][1]['items'][0]['itemId']);
    }

    public function testRoundTripAssessmentContainerPreservesKeyFields(): void
    {
        $input = [
            'id' => 'a-1',
            'name' => 'PHQ-9',
            'type' => 'Assessment',
            'items' => [
                [
                    'id' => 'a-1',
                    'name' => 'PHQ-9',
                    'type' => 'Assessment',
                    'assessmentId' => 5,
                    'uid' => 'u-1',
                    'assessmentUuid' => 'au-1',
                    'itemId' => 3,
                    'resultId' => null,
                ],
            ],
        ];

        $serializer = $this->serializer();
        $out = $serializer->serialize($serializer->deserialize($input));

        $this->assertSame('a-1', $out['id']);
        $this->assertSame('PHQ-9', $out['name']);
        $this->assertSame('Assessment', $out['type']);
        $this->assertCount(1, $out['items']);
        $child = $out['items'][0];
        $this->assertSame('Assessment', $child['type']);
        $this->assertSame(5, $child['assessmentId']);
        $this->assertSame('u-1', $child['uid']);
        $this->assertSame('au-1', $child['assessmentUuid']);
        $this->assertSame(3, $child['itemId']);
        $this->assertNull($child['resultId']);
    }

    public function testDeserializeUnknownTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid assignment type');

        $this->serializer()->deserialize(['id' => 'x', 'name' => 'n', 'type' => 'Bogus']);
    }

    public function testDeserializeNullTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid assignment type');

        $this->serializer()->deserialize(['id' => 'x', 'name' => 'n', 'type' => null]);
    }

    /**
     * Finding: "TemplateProfile" is a valid Assignment model type (and a group type),
     * but AssignmentSerializer::deserialize() does not handle it and rejects it.
     */
    public function testDeserializeTemplateProfileThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid assignment type');

        $this->serializer()->deserialize([
            'id' => 'tp-1',
            'name' => 'Profile',
            'type' => 'TemplateProfile',
            'profileId' => 'p-1',
        ]);
    }

    /**
     * A payload with no "type" key reaches the unknown-type branch and throws
     * InvalidArgumentException. Reading the absent key first emits an "Undefined array
     * key" warning under PHP 8, which PHPUnit may escalate; swallow it so the thrown
     * exception can be characterized.
     */
    public function testDeserializeMissingTypeThrows(): void
    {
        set_error_handler(static function (): bool {
            return true;
        });
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->serializer()->deserialize(['id' => 'x', 'name' => 'n']);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * The recursion guard rejects nesting deeper than 5 levels. Passing an explicit
     * depth above the threshold triggers it without having to build a deep tree.
     */
    public function testDeserializeTooDeepThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many levels of nesting in assignment');

        $this->serializer()->deserialize(['type' => 'AssessmentGroup', 'id' => 'g'], 6);
    }
}
