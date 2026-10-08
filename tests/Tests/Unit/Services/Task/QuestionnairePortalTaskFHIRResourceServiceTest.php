<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Services\Task;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\FHIR\R4\FHIRElement\FHIRTaskStatus;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\QuestionnairePortalTaskFHIRResourceService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for QuestionnairePortalTaskFHIRResourceService: builds a FHIR Task from
 * a portal questionnaire-template record (read direction) and guards the write path.
 *
 * No DB: the service has no injected repository, and parseOpenEMRRecord only uses the
 * pure UtilsService codeable-concept/reference/canonical helpers. The update() tests
 * cover the type-check guard and the non-completed "status update not supported" branch,
 * both of which run before any DB access; the completed-status write path needs a live
 * DB + QuestionnaireResponseService and is deferred to the integration suite.
 */
class QuestionnairePortalTaskFHIRResourceServiceTest extends TestCase
{
    /** Unwrap a FHIR primitive wrapper to its scalar value for robust assertions. */
    private static function scalar(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'getValue')) {
            return $value->getValue();
        }
        return $value;
    }

    public function testSupportsOwnCodeAndRejectsOthers(): void
    {
        $service = new QuestionnairePortalTaskFHIRResourceService();
        $this->assertTrue($service->supportsCode(QuestionnairePortalTaskFHIRResourceService::FHIR_TASK_CODE));
        $this->assertTrue($service->supportsCode('complete-questionnaire'));
        $this->assertFalse($service->supportsCode('complete-dac-assignment'));
        $this->assertFalse($service->supportsCode('questionnaire'));
        $this->assertFalse($service->supportsCode(''));
    }

    /**
     * Happy path: a questionnaire portal record maps to a Task with description, the
     * complete-questionnaire code, a canonical Questionnaire input, owner/for patient
     * references and the record status.
     */
    public function testParseOpenEMRRecordBuildsTask(): void
    {
        $record = [
            'id' => 'template-55',
            'type_display_text' => 'Intake Questionnaire',
            'type_uuid' => 'questionnaire-uuid-abc',
            'owner_uuid' => 'patient-uuid-xyz',
            'patient_uuid' => 'patient-uuid-xyz',
            'status' => 'ready',
        ];

        $service = new QuestionnairePortalTaskFHIRResourceService();
        $task = $service->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRTask::class, $task);
        $this->assertSame('template-55', self::scalar($task->getId()));
        $this->assertSame('Intake Questionnaire', self::scalar($task->getDescription()));
        $this->assertSame('ready', self::scalar($task->getStatus()));

        $coding = $task->getCode()->getCoding();
        $this->assertNotEmpty($coding);
        $this->assertSame(
            QuestionnairePortalTaskFHIRResourceService::FHIR_TASK_CODE,
            self::scalar($coding[0]->getCode())
        );

        // input[0]: a canonical Questionnaire URL referencing the questionnaire uuid
        $inputs = $task->getInput();
        $this->assertCount(1, $inputs);
        $inputTypeCoding = $inputs[0]->getType()->getCoding();
        $this->assertSame('questionnaire', self::scalar($inputTypeCoding[0]->getCode()));
        $canonical = (string) self::scalar($inputs[0]->getValueCanonical());
        $this->assertStringContainsString('Questionnaire', $canonical);
        $this->assertStringContainsString('questionnaire-uuid-abc', $canonical);

        // owner and for are both the patient (portal self-service)
        $this->assertSame('Patient/patient-uuid-xyz', self::scalar($task->getOwner()->getReference()));
        $this->assertSame('Patient/patient-uuid-xyz', self::scalar($task->getFor()->getReference()));

        $this->assertSame('1', self::scalar($task->getMeta()->getVersionId()));
    }

    /**
     * The record 'status' is passed through verbatim to the Task status.
     */
    public function testParseOpenEMRRecordPassesThroughStatus(): void
    {
        $record = [
            'id' => 'template-56',
            'type_display_text' => 'Follow-up',
            'type_uuid' => 'questionnaire-uuid-def',
            'owner_uuid' => 'patient-uuid-2',
            'patient_uuid' => 'patient-uuid-2',
            'status' => 'completed',
        ];

        $service = new QuestionnairePortalTaskFHIRResourceService();
        $task = $service->parseOpenEMRRecord($record);

        $this->assertSame('completed', self::scalar($task->getStatus()));
    }

    public function testUpdateRejectsNonTaskResource(): void
    {
        $service = new QuestionnairePortalTaskFHIRResourceService();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FHIR resource type passed to update');
        $service->update('some-id', new FHIRQuestionnaire());
    }

    /**
     * Non-completed status updates are rejected cleanly before any DB access: the only
     * supported write is a completed transition, so a 'ready' Task fails with the
     * "status update not supported" error.
     */
    public function testUpdateWithNonCompletedStatusIsNotSupported(): void
    {
        $service = new QuestionnairePortalTaskFHIRResourceService();
        $task = new FHIRTask();
        // status is a FHIR primitive object in a hydrated resource; update() reads
        // getStatus()->getValue(), so set a wrapper (not a raw string).
        $task->setStatus(new FHIRTaskStatus(['value' => 'ready']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status update not supported on this resource server');
        $service->update('template-55', $task);
    }
}
