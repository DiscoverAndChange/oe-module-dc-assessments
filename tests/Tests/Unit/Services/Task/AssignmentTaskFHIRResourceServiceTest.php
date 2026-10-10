<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Services\Task;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\AssignmentTaskFHIRResourceService;
use OpenEMR\Services\FHIR\IPatientCompartmentResourceService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for AssignmentTaskFHIRResourceService: the sub-service that turns a
 * flat assignment/assignment-item record into a FHIR Task resource (read direction)
 * and guards the write path (update).
 *
 * No DB: the repository ctor dependency is mocked, and parseOpenEMRRecord only uses
 * the pure UtilsService reference/codeable-concept helpers. The update() tests cover
 * only the early guard/clean-fail branches (type check, item-update "not supported",
 * unknown-id) which run before any DB write; the completed-status write path needs a
 * live DB + QuestionnaireResponseService and is deferred to the integration suite.
 */
class AssignmentTaskFHIRResourceServiceTest extends TestCase
{
    /**
     * Unwrap a FHIR primitive wrapper (FHIRString/FHIRId/...) to its scalar value so
     * assertions are robust whether a generated getter hands back the wrapper or a raw
     * scalar.
     */
    private static function scalar(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'getValue')) {
            return $value->getValue();
        }
        return $value;
    }

    private function newService(?AssignmentRepository $repository = null): AssignmentTaskFHIRResourceService
    {
        return new AssignmentTaskFHIRResourceService(
            $repository ?? $this->createMock(AssignmentRepository::class)
        );
    }

    /**
     * Regression guard for the patient-scoped Task search.
     *
     * As the only sub-service mapped by TaskFHIRResourceService, this service is reached
     * with the bound patient uuid ($puuidBind) on every patient-context Task search. Newer
     * OpenEMR core (patient-compartment enforcement, core PR #13855) throws a SearchFieldException
     * ("Patient-scoped access to this resource is not permitted.") for any service that is
     * handed a patient bind but does NOT declare IPatientCompartmentResourceService -- which
     * TaskFHIRResourceService::getAll() then swallows, returning an EMPTY bundle to the patient
     * while the provider (no bind) still sees results. So this service MUST declare the
     * interface, and its patient context field must map to the client uuid column.
     */
    public function testIsPatientCompartmentServiceBoundToClientUuid(): void
    {
        $service = $this->newService();
        $this->assertInstanceOf(IPatientCompartmentResourceService::class, $service);

        $field = $service->getPatientContextSearchField();
        $this->assertSame('patient', $field->getName());
        $mapped = array_map(
            static fn($serviceField) => $serviceField->getField(),
            $field->getMappedFields()
        );
        $this->assertSame(['client_uuid'], $mapped);
    }

    public function testSupportsOwnCodeAndRejectsOthers(): void
    {
        $service = $this->newService();
        $this->assertTrue($service->supportsCode(AssignmentTaskFHIRResourceService::DAC_ASSIGNMENT));
        $this->assertTrue($service->supportsCode('complete-dac-assignment'));
        $this->assertFalse($service->supportsCode('complete-questionnaire'));
        $this->assertFalse($service->supportsCode('foo'));
        $this->assertFalse($service->supportsCode(''));
    }

    /**
     * Happy path: a record with an assignment parent, assigned date and no completed
     * date maps to a 'ready' Task carrying the DAC assignment code, a json input, the
     * patient owner, the partOf parent Task and the authoredOn date.
     */
    public function testParseOpenEMRRecordBuildsReadyTask(): void
    {
        $record = [
            'id' => 'assignment-item-1',
            'type' => 'Questionnaire',
            'clientId' => 'client-uuid-123',
            'assignment_uuid' => 'assignment-uuid-999',
            'dateAssigned' => '2026-01-02T03:04:05+00:00',
        ];

        $task = $this->newService()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRTask::class, $task);
        $this->assertSame('assignment-item-1', self::scalar($task->getId()));
        $this->assertSame('ready', self::scalar($task->getStatus()));

        // top-level Task code is the DAC assignment code
        $coding = $task->getCode()->getCoding();
        $this->assertNotEmpty($coding);
        $this->assertSame(
            AssignmentTaskFHIRResourceService::DAC_ASSIGNMENT,
            self::scalar($coding[0]->getCode())
        );

        // input[0]: type coding echoes the record 'type', valueString is the json record
        $inputs = $task->getInput();
        $this->assertCount(1, $inputs);
        $inputTypeCoding = $inputs[0]->getType()->getCoding();
        $this->assertSame('Questionnaire', self::scalar($inputTypeCoding[0]->getCode()));
        $decoded = json_decode((string) self::scalar($inputs[0]->getValueString()), true);
        $this->assertSame($record, $decoded);

        // owner is the patient; partOf points at the parent assignment Task
        $this->assertSame('Patient/client-uuid-123', self::scalar($task->getOwner()->getReference()));
        $partOf = $task->getPartOf();
        $this->assertCount(1, $partOf);
        $this->assertSame('Task/assignment-uuid-999', self::scalar($partOf[0]->getReference()));

        $this->assertSame('2026-01-02T03:04:05+00:00', self::scalar($task->getAuthoredOn()));
        $this->assertSame('1', self::scalar($task->getMeta()->getVersionId()));
    }

    /**
     * A record with a dateCompleted maps to a 'completed' Task.
     */
    public function testParseOpenEMRRecordMarksCompletedWhenDateCompletedPresent(): void
    {
        $record = [
            'id' => 'assignment-item-2',
            'type' => 'Assessment',
            'clientId' => 'client-uuid-456',
            'dateCompleted' => '2026-02-03T04:05:06+00:00',
        ];

        $task = $this->newService()->parseOpenEMRRecord($record);

        $this->assertSame('completed', self::scalar($task->getStatus()));
    }

    /**
     * When no assignment_uuid / dateAssigned is present, partOf and authoredOn are not
     * populated (the empty() guards skip them).
     */
    public function testParseOpenEMRRecordOmitsPartOfAndAuthoredOnWhenAbsent(): void
    {
        $record = [
            'id' => 'standalone-1',
            'type' => 'Questionnaire',
            'clientId' => 'client-uuid-789',
        ];

        $task = $this->newService()->parseOpenEMRRecord($record);

        $this->assertEmpty($task->getPartOf());
        $this->assertNull($task->getAuthoredOn());
        $this->assertSame('ready', self::scalar($task->getStatus()));
    }

    public function testUpdateRejectsNonTaskResource(): void
    {
        $service = $this->newService();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FHIR resource type passed to update');
        $service->update('some-id', new FHIRQuestionnaire());
    }

    /**
     * v0.12.1 clean-fail: when the id resolves to an individual assignment ITEM, the
     * update path throws a clear "not yet supported" error instead of fataling on the
     * never-defined updateAssignmentItem(). Covered without a DB write.
     */
    public function testUpdateOfAssignmentItemFailsCleanly(): void
    {
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('getAssignmentByUuid')->willReturn(null);
        $repository->method('getAssignmentForAssignmentItemUuid')
            ->willReturn($this->createMock(Assignment::class));

        $service = $this->newService($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Updating an individual assignment item via a Task is not yet supported');
        $service->update('item-uuid', new FHIRTask());
    }

    /**
     * When neither an assignment nor an assignment item matches the id, update() rejects
     * the id (the fixed guard no longer throws when only one lookup is null).
     */
    public function testUpdateRejectsUnknownId(): void
    {
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('getAssignmentByUuid')->willReturn(null);
        $repository->method('getAssignmentForAssignmentItemUuid')->willReturn(null);

        $service = $this->newService($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FHIR resource id passed to update');
        $service->update('missing-uuid', new FHIRTask());
    }
}
