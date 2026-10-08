<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Services;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\AssignmentTaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\QuestionnairePortalTaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) tests for the TaskFHIRResourceService delegation service.
 *
 * The service extends OpenEMR's FhirServiceBase and routes a FHIR Task search to a mapped
 * sub-service by the Task.code token. Only the AssignmentTask sub-service is mapped in the
 * constructor (the portal sub-service add is commented out in source). All collaborators
 * are mocked; parseOpenEMRRecord is covered directly since it maps a plain array to a
 * FHIRTask using only pure UtilsService string helpers (no DB).
 */
class TaskFHIRResourceServiceTest extends TestCase
{
    /**
     * @return array{0: TaskFHIRResourceService, 1: QuestionnairePortalTaskFHIRResourceService&\PHPUnit\Framework\MockObject\MockObject, 2: AssignmentTaskFHIRResourceService&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function makeService(): array
    {
        $portal = $this->createMock(QuestionnairePortalTaskFHIRResourceService::class);
        $assignment = $this->createMock(AssignmentTaskFHIRResourceService::class);
        $service = new TaskFHIRResourceService($portal, $assignment);
        return [$service, $portal, $assignment];
    }

    /**
     * loadSearchParameters() is protected; invoke it via reflection and assert the FHIR
     * Task search keys the service advertises are present.
     */
    public function testLoadSearchParametersDeclaresExpectedKeys(): void
    {
        [$service] = $this->makeService();

        $method = new \ReflectionMethod($service, 'loadSearchParameters');
        $method->setAccessible(true);
        $params = $method->invoke($service);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('_id', $params);
        $this->assertArrayHasKey('patient', $params);
        $this->assertArrayHasKey('code', $params);
    }

    /**
     * With a code parameter present, getAll() resolves the mapped sub-service whose
     * supportsCode() returns true and delegates the search to it, returning that
     * sub-service's ProcessingResult unchanged.
     */
    public function testGetAllWithCodeDelegatesToSupportingService(): void
    {
        [$service, $portal, $assignment] = $this->makeService();

        $expected = new ProcessingResult();
        $expected->addData(['sentinel' => true]);

        $assignment->method('supportsCode')->willReturn(true);
        $assignment->expects($this->once())
            ->method('getAll')
            ->willReturn($expected);
        // The portal sub-service is not mapped in the constructor, so it is never consulted.
        $portal->expects($this->never())->method('getAll');

        $result = $service->getAll(['code' => 'dc_assignment']);

        $this->assertSame($expected, $result);
    }

    /**
     * Without a code parameter, getAll() falls through to searchAllServices, which fans
     * the search out to every mapped sub-service's getAll().
     */
    public function testGetAllWithoutCodeSearchesAllMappedServices(): void
    {
        [$service, $portal, $assignment] = $this->makeService();

        $params = ['_id' => 'task-uuid'];

        $assignment->expects($this->once())
            ->method('getAll')
            ->with($params, null)
            ->willReturn(new ProcessingResult());
        $portal->expects($this->never())->method('getAll');

        $result = $service->getAll($params);

        $this->assertInstanceOf(ProcessingResult::class, $result);
    }

    /**
     * createProvenanceResource() rejects a record that is not a FHIRTask by throwing an
     * InvalidArgumentException.
     */
    public function testCreateProvenanceResourceWithWrongTypeThrows(): void
    {
        [$service] = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->createProvenanceResource(new FHIRQuestionnaireResponse());
    }

    /**
     * parseOpenEMRRecord() maps a questionnaire-type Task record (with a patient owner) to
     * a FHIRTask: id, status, a Questionnaire input reference and a Patient owner reference.
     */
    public function testParseOpenEMRRecordBuildsQuestionnaireTask(): void
    {
        [$service] = $this->makeService();

        $record = [
            'id' => 'task-123',
            'type' => 'questionnaire',
            'type_id' => 'q-uuid',
            'owner_type' => 'patient',
            'owner_id' => 'pat-uuid',
            'status' => 'ready',
        ];

        $task = $service->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRTask::class, $task);
        $this->assertSame('task-123', $task->getId()->getValue());
        $this->assertSame('ready', $task->getStatus());
        $this->assertNotNull($task->getCode());
        $this->assertNotNull($task->getMeta());

        // Owner maps to a relative Patient reference.
        $this->assertSame('Patient/pat-uuid', $task->getOwner()->getReference());

        // The questionnaire is carried as a Task.input valueReference.
        $input = $task->getInput();
        $this->assertCount(1, $input);
        $this->assertSame('Questionnaire/q-uuid', $input[0]->getValueReference()->getReference());
    }

    /**
     * parseOpenEMRRecord() maps a dc_assignment-type Task record (with a user owner) to a
     * FHIRTask: the assignment payload is carried as an input valueString and the owner is
     * a Practitioner reference.
     */
    public function testParseOpenEMRRecordBuildsAssignmentTask(): void
    {
        [$service] = $this->makeService();

        $record = [
            'id' => 'task-456',
            'type' => 'dc_assignment',
            'assignment' => 'the-assignment-payload',
            'owner_type' => 'user',
            'owner_id' => 'user-uuid',
            'status' => 'completed',
        ];

        $task = $service->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRTask::class, $task);
        $this->assertSame('task-456', $task->getId()->getValue());
        // A status with no special mapping passes through unchanged.
        $this->assertSame('completed', $task->getStatus());

        // User owner maps to a relative Practitioner reference.
        $this->assertSame('Practitioner/user-uuid', $task->getOwner()->getReference());

        // The assignment is carried as a Task.input valueString.
        $input = $task->getInput();
        $this->assertCount(1, $input);
        $this->assertSame('the-assignment-payload', $input[0]->getValueString());
    }
}
