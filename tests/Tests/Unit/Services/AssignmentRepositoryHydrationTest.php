<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedTemplateProfile;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignmentRepository's private
 * hydration methods (DB-row array -> typed model object). These lock in the
 * exact mixed-array -> typed-object behavior that the upcoming source-typing
 * pass rewrites.
 *
 * Technique: the hydrators are private, so they are invoked via reflection with
 * a crafted record. The lazy "generate a uuid and write it to the DB" branches
 * are avoided by always supplying the relevant *_uuid key as 16 raw bytes, so
 * every path exercised here is pure.
 */
class AssignmentRepositoryHydrationTest extends TestCase
{
    // Canonical uuid strings used to build raw-byte record values. Distinct per
    // field so each test proves the right column maps to the right model prop.
    private const ASSIGNMENT_UUID = '00000000-0000-4000-8000-000000000001';
    private const ITEM_UUID = '00000000-0000-4000-8000-000000000002';
    private const CLIENT_UUID = '00000000-0000-4000-8000-000000000003';
    private const APPT_UUID = '00000000-0000-4000-8000-000000000004';
    private const ASSESSMENT_UUID = '00000000-0000-4000-8000-000000000005';
    private const ASSET_UUID = '00000000-0000-4000-8000-000000000006';
    private const DOCUMENT_UUID = '00000000-0000-4000-8000-000000000007';

    private AssignmentRepository $repo;

    protected function setUp(): void
    {
        // The real constructor takes no arguments (the spec's example signature
        // is stale); an empty constructor means no DB access at construction.
        $this->repo = new AssignmentRepository();
    }

    /**
     * @param array<string, mixed> $record
     */
    private function invoke(string $method, array $record, ?Assignment $target = null): Assignment
    {
        $m = new \ReflectionMethod($this->repo, $method);
        $m->setAccessible(true);
        if ($target === null) {
            // single-arg hydrators (hydrateAssignmentFromRecord / hydrateItemFromRecord)
            return $m->invoke($this->repo, $record);
        }
        $m->invoke($this->repo, $record, $target);
        return $target;
    }

    private static function bytes(string $uuid): string
    {
        return UuidRegistry::uuidToBytes($uuid);
    }

    /**
     * The expected string a hydrator stores for a given uuid: the hydrator runs
     * raw bytes back through UuidRegistry::uuidToString, so we compare against
     * that round-trip rather than assuming the canonical literal matches.
     */
    private static function str(string $uuid): string
    {
        return UuidRegistry::uuidToString(self::bytes($uuid));
    }

    // ---------------------------------------------------------------------
    // populateDatesForAssignment
    // ---------------------------------------------------------------------

    public function testPopulateDatesParsesBothDates(): void
    {
        $assignment = new Assignment();
        $this->invoke('populateDatesForAssignment', [
            'date_assigned' => '2026-01-02 03:04:05.000000',
            'date_completed' => '2026-02-03 04:05:06.000000',
        ], $assignment);

        $this->assertInstanceOf(\DateTime::class, $assignment->getDateAssigned());
        $this->assertSame('2026-01-02 03:04:05', $assignment->getDateAssigned()->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(\DateTime::class, $assignment->getDateCompleted());
        $this->assertSame('2026-02-03 04:05:06', $assignment->getDateCompleted()->format('Y-m-d H:i:s'));
    }

    public function testPopulateDatesLeavesDatesNullWhenEmpty(): void
    {
        $assignment = new Assignment();
        $this->invoke('populateDatesForAssignment', [
            'date_assigned' => null,
            'date_completed' => '',
        ], $assignment);

        $this->assertNull($assignment->getDateAssigned());
        $this->assertNull($assignment->getDateCompleted());
    }

    // ---------------------------------------------------------------------
    // hydrateAssignedAssessmentFromRecord
    // ---------------------------------------------------------------------

    public function testHydrateAssignedAssessmentFromRecord(): void
    {
        $record = [
            'id' => 77,
            'assessmentblob_id' => 101,
            'assessmentblob_uuid' => self::bytes(self::ASSESSMENT_UUID),
            'assessmentblob_name' => 'PHQ-9',
            'assessmentblob_uid' => 'phq9-uid',
            'assessmentresultblob_id' => '55',
        ];
        /** @var AssignedAssessment $a */
        $a = $this->invoke('hydrateAssignedAssessmentFromRecord', $record, new AssignedAssessment());

        $this->assertSame(77, $a->getItemId());
        $this->assertSame(101, $a->getAssessmentId());
        $this->assertSame(self::str(self::ASSESSMENT_UUID), $a->getAssessmentUuid());
        $this->assertSame('PHQ-9', $a->getName());
        $this->assertSame('phq9-uid', $a->getUid());
        $this->assertSame('55', $a->getResultId());
        $this->assertSame('Assessment', $a->getType());
    }

    public function testHydrateAssignedAssessmentFromRecordLeavesResultIdNullWhenEmpty(): void
    {
        $record = [
            'id' => 1,
            'assessmentblob_id' => 2,
            'assessmentblob_uuid' => self::bytes(self::ASSESSMENT_UUID),
            'assessmentblob_name' => 'n',
            'assessmentblob_uid' => 'u',
            'assessmentresultblob_id' => null,
        ];
        /** @var AssignedAssessment $a */
        $a = $this->invoke('hydrateAssignedAssessmentFromRecord', $record, new AssignedAssessment());

        $this->assertNull($a->getResultId());
    }

    // ---------------------------------------------------------------------
    // hydrateAssignedAssessmentGroupFromRecord
    // ---------------------------------------------------------------------

    public function testHydrateAssignedAssessmentGroupFromRecordNoItems(): void
    {
        $record = [
            'assessmentgroup_id' => 9,
            'assessmentgroup_name' => 'Intake Battery',
        ];
        /** @var AssignedAssessmentGroup $g */
        $g = $this->invoke('hydrateAssignedAssessmentGroupFromRecord', $record, new AssignedAssessmentGroup());

        $this->assertSame(9, $g->getAssessmentGroupId());
        $this->assertSame('Intake Battery', $g->getName());
        $this->assertSame('AssessmentGroup', $g->getType());
        $this->assertSame([], $g->getItems());
    }

    public function testHydrateAssignedAssessmentGroupFromRecordHydratesItems(): void
    {
        $record = [
            'assessmentgroup_id' => 9,
            'assessmentgroup_name' => 'Battery',
            'items' => [$this->assessmentItem()],
        ];
        /** @var AssignedAssessmentGroup $g */
        $g = $this->invoke('hydrateAssignedAssessmentGroupFromRecord', $record, new AssignedAssessmentGroup());

        $this->assertCount(1, $g->getItems());
        $this->assertInstanceOf(AssignedAssessment::class, $g->getItems()[0]);
        $this->assertSame(self::str(self::ITEM_UUID), $g->getItems()[0]->getId());
    }

    // ---------------------------------------------------------------------
    // hydrateAssignedTemplateProfileFromRecord
    // ---------------------------------------------------------------------

    public function testHydrateAssignedTemplateProfileFromRecord(): void
    {
        $record = [
            'profile_id' => '42',
            'profile_name' => 'Depression Profile',
        ];
        /** @var AssignedTemplateProfile $p */
        $p = $this->invoke('hydrateAssignedTemplateProfileFromRecord', $record, new AssignedTemplateProfile());

        $this->assertSame('42', $p->getProfileId());
        $this->assertSame('Depression Profile', $p->getName());
        $this->assertSame('TemplateProfile', $p->getType());
        $this->assertSame([], $p->getItems());
    }

    public function testHydrateAssignedTemplateProfileFromRecordHydratesQuestionnaireItems(): void
    {
        $record = [
            'profile_id' => '42',
            'profile_name' => 'Profile',
            'items' => [$this->questionnaireItem()],
        ];
        /** @var AssignedTemplateProfile $p */
        $p = $this->invoke('hydrateAssignedTemplateProfileFromRecord', $record, new AssignedTemplateProfile());

        $this->assertCount(1, $p->getItems());
        $this->assertInstanceOf(AssignedQuestionnaire::class, $p->getItems()[0]);
    }

    // ---------------------------------------------------------------------
    // hydrateAssignedLibraryAssetFromRecord
    // ---------------------------------------------------------------------

    public function testHydrateAssignedLibraryAssetFromRecord(): void
    {
        $record = [
            'asset_id' => 500,
            'asset_uuid' => self::bytes(self::ASSET_UUID),
            'asset_name' => 'Coping Worksheet',
            'assetresultblob_id' => null,
            'date_assigned' => '2026-03-04 05:06:07.000000',
        ];
        /** @var AssignedLibraryAsset $asset */
        $asset = $this->invoke('hydrateAssignedLibraryAssetFromRecord', $record, new AssignedLibraryAsset());

        $this->assertSame(500, $asset->getAssetId());
        $this->assertSame(self::str(self::ASSET_UUID), $asset->getAssetUuid());
        $this->assertSame('Coping Worksheet', $asset->getName());
        $this->assertNull($asset->getResultId());
        $this->assertSame('LibraryAsset', $asset->getType());
        $this->assertInstanceOf(\DateTime::class, $asset->getDateAssigned());
        $this->assertSame('2026-03-04', $asset->getDateAssigned()->format('Y-m-d'));
    }

    // ---------------------------------------------------------------------
    // hydrateAssignedQuestionnaireFromRecord
    // ---------------------------------------------------------------------

    public function testHydrateAssignedQuestionnaireFromRecord(): void
    {
        $record = [
            'questionnaire_uuid' => 'questionnaire-abc',
            'questionnaire_name' => 'GAD-7',
            'questionnaire_response_id' => '900',
            'date_assigned' => '2026-04-05 06:07:08.000000',
        ];
        /** @var AssignedQuestionnaire $q */
        $q = $this->invoke('hydrateAssignedQuestionnaireFromRecord', $record, new AssignedQuestionnaire());

        $this->assertSame('questionnaire-abc', $q->getQuestionnaireId());
        $this->assertSame('GAD-7', $q->getName());
        $this->assertSame('900', $q->getResultId());
        $this->assertSame('Questionnaire', $q->getType());
        $this->assertInstanceOf(\DateTime::class, $q->getDateAssigned());
    }

    // ---------------------------------------------------------------------
    // hydrateDocumentTemplateProfile
    // ---------------------------------------------------------------------

    public function testHydrateDocumentTemplateProfileWithQuestionnaireAndDocument(): void
    {
        $record = [
            'template_id' => 7,
            'document_uuid' => self::bytes(self::DOCUMENT_UUID),
            'questionnaire_uuid' => 'questionnaire-xyz',
            'questionnaire_name' => 'Intake Form',
            'questionnaire_response_id' => '12',
        ];
        /** @var AssignedQuestionnaire $q */
        $q = $this->invoke('hydrateDocumentTemplateProfile', $record, new AssignedQuestionnaire());

        $this->assertSame(7, $q->getDocumentTemplateId());
        $this->assertSame(self::str(self::DOCUMENT_UUID), $q->getDocumentId());
        // document-template profile delegates to the questionnaire hydrator
        $this->assertSame('questionnaire-xyz', $q->getQuestionnaireId());
        $this->assertSame('Intake Form', $q->getName());
        $this->assertSame('12', $q->getResultId());
    }

    public function testHydrateDocumentTemplateProfileLeavesDocumentIdNullWhenUuidEmpty(): void
    {
        $record = [
            'template_id' => 7,
            'document_uuid' => null,
            'questionnaire_uuid' => 'q-1',
            'questionnaire_name' => 'n',
            'questionnaire_response_id' => null,
        ];
        /** @var AssignedQuestionnaire $q */
        $q = $this->invoke('hydrateDocumentTemplateProfile', $record, new AssignedQuestionnaire());

        $this->assertNull($q->getDocumentId());
        $this->assertSame(7, $q->getDocumentTemplateId());
    }

    // ---------------------------------------------------------------------
    // hydrateItemFromRecord (routing)
    // ---------------------------------------------------------------------

    public function testHydrateItemFromRecordRoutesToAssessment(): void
    {
        $item = $this->invoke('hydrateItemFromRecord', $this->assessmentItem());

        $this->assertInstanceOf(AssignedAssessment::class, $item);
        $this->assertSame(self::str(self::ITEM_UUID), $item->getId());
        $this->assertSame(101, $item->getAssessmentId());
    }

    public function testHydrateItemFromRecordRoutesToLibraryAsset(): void
    {
        $item = $this->invoke('hydrateItemFromRecord', [
            'uuid' => self::bytes(self::ITEM_UUID),
            'asset_id' => 500,
            'asset_uuid' => self::bytes(self::ASSET_UUID),
            'asset_name' => 'Worksheet',
            'assetresultblob_id' => null,
        ]);

        $this->assertInstanceOf(AssignedLibraryAsset::class, $item);
        $this->assertSame(self::str(self::ITEM_UUID), $item->getId());
        $this->assertSame(self::str(self::ASSET_UUID), $item->getAssetUuid());
    }

    public function testHydrateItemFromRecordRoutesTemplateIdToQuestionnaire(): void
    {
        $item = $this->invoke('hydrateItemFromRecord', [
            'uuid' => self::bytes(self::ITEM_UUID),
            'template_id' => 7,
            'document_uuid' => null,
            'questionnaire_uuid' => 'q-abc',
            'questionnaire_name' => 'Form',
            'questionnaire_response_id' => null,
        ]);

        $this->assertInstanceOf(AssignedQuestionnaire::class, $item);
        $this->assertSame(7, $item->getDocumentTemplateId());
        $this->assertSame('q-abc', $item->getQuestionnaireId());
    }

    public function testHydrateItemFromRecordRoutesQuestionnaireUuidToQuestionnaire(): void
    {
        $item = $this->invoke('hydrateItemFromRecord', $this->questionnaireItem());

        $this->assertInstanceOf(AssignedQuestionnaire::class, $item);
        $this->assertNull($item->getDocumentTemplateId(), 'plain questionnaire item has no document template');
        $this->assertSame('questionnaire-abc', $item->getQuestionnaireId());
    }

    public function testHydrateItemFromRecordSetsAuditClientAndId(): void
    {
        $record = $this->assessmentItem();
        $record['audit_id'] = 321;
        $record['client_uuid'] = self::bytes(self::CLIENT_UUID);

        $item = $this->invoke('hydrateItemFromRecord', $record);

        $this->assertSame(321, $item->getAuditId());
        $this->assertSame(self::str(self::CLIENT_UUID), $item->getClientId());
        $this->assertSame(self::str(self::ITEM_UUID), $item->getId());
    }

    public function testHydrateItemFromRecordThrowsOnUnknownType(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown assignment item type');
        $this->invoke('hydrateItemFromRecord', ['uuid' => self::bytes(self::ITEM_UUID)]);
    }

    // ---------------------------------------------------------------------
    // hydrateAssignmentFromRecord (top-level routing)
    // ---------------------------------------------------------------------

    public function testHydrateAssignmentFromRecordBuildsAssessmentGroup(): void
    {
        $record = [
            'assignment_uuid' => self::bytes(self::ASSIGNMENT_UUID),
            'assessmentgroup_id' => 9,
            'assessmentgroup_name' => 'Battery',
            'items' => [$this->assessmentItem()],
            'client_uuid' => self::bytes(self::CLIENT_UUID),
        ];
        $assignment = $this->invoke('hydrateAssignmentFromRecord', $record);

        $this->assertInstanceOf(AssignedAssessmentGroup::class, $assignment);
        $this->assertSame(self::str(self::ASSIGNMENT_UUID), $assignment->getId());
        $this->assertSame(self::str(self::CLIENT_UUID), $assignment->getClientId());
        $this->assertSame(9, $assignment->getAssessmentGroupId());
        $this->assertCount(1, $assignment->getItems());
    }

    public function testHydrateAssignmentFromRecordBuildsTemplateProfile(): void
    {
        $record = [
            'assignment_uuid' => self::bytes(self::ASSIGNMENT_UUID),
            'profile_id' => '42',
            'profile_name' => 'Profile',
            'items' => [$this->questionnaireItem()],
        ];
        $assignment = $this->invoke('hydrateAssignmentFromRecord', $record);

        $this->assertInstanceOf(AssignedTemplateProfile::class, $assignment);
        $this->assertSame('42', $assignment->getProfileId());
        $this->assertCount(1, $assignment->getItems());
    }

    public function testHydrateAssignmentFromRecordSingleItemPopulatesParentTypeAndName(): void
    {
        $record = [
            'assignment_uuid' => self::bytes(self::ASSIGNMENT_UUID),
            'calendar_event_uuid' => self::bytes(self::APPT_UUID),
            'client_uuid' => self::bytes(self::CLIENT_UUID),
            'assignment_audit_id' => 888,
            'date_assigned' => '2026-05-06 07:08:09.000000',
            'items' => [$this->assessmentItem()],
        ];
        $assignment = $this->invoke('hydrateAssignmentFromRecord', $record);

        // base (non-group) Assignment built from a single item
        $this->assertSame(Assignment::class, get_class($assignment));
        $this->assertSame(self::str(self::ASSIGNMENT_UUID), $assignment->getId());
        $this->assertSame(self::str(self::APPT_UUID), $assignment->getAppointmentId());
        $this->assertSame(self::str(self::CLIENT_UUID), $assignment->getClientId());
        $this->assertSame(888, $assignment->getAuditId());
        // single item hoists its type/name onto the parent assignment
        $this->assertSame('Assessment', $assignment->getType());
        $this->assertSame('PHQ-9', $assignment->getName());
        $this->assertInstanceOf(\DateTime::class, $assignment->getDateAssigned());
        $this->assertSame('2026-05-06', $assignment->getDateAssigned()->format('Y-m-d'));
    }

    // ---------------------------------------------------------------------
    // crafted-row builders (all *_uuid keys are raw bytes to stay off the DB)
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function assessmentItem(): array
    {
        return [
            'id' => 77,
            'uuid' => self::bytes(self::ITEM_UUID),
            'assessmentblob_id' => 101,
            'assessmentblob_uuid' => self::bytes(self::ASSESSMENT_UUID),
            'assessmentblob_name' => 'PHQ-9',
            'assessmentblob_uid' => 'phq9-uid',
            'assessmentresultblob_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function questionnaireItem(): array
    {
        return [
            'uuid' => self::bytes(self::ITEM_UUID),
            'questionnaire_uuid' => 'questionnaire-abc',
            'questionnaire_name' => 'GAD-7',
            'questionnaire_response_id' => null,
        ];
    }
}
