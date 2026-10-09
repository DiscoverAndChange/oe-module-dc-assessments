<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AclIntegration;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AssignmentFixture;
use OpenEMR\Services\PatientService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Support/AclIntegration.php';
require_once __DIR__ . '/../../../Support/AssignmentFixture.php';

/**
 * DB-backed integration test for the questionnaire/assessment-response -> assignment-completion
 * insert path (AssessmentResponseBlobFHIRResourceService::insertOpenEmrRecord), the server side
 * of the patient SPA submitting a completed assessment. loginAsAdmin() grants the ACL the write
 * path checks; the AssignmentCompleter is mocked so the completion side effects (portal activity
 * / notifications) are not exercised here.
 */
class AssessmentResponseBlobInsertTest extends TestCase
{
    use AclIntegration;
    use AssignmentFixture;

    private const RESULT_ID = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    private string $itemUuid = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
        $this->seedPatientAndAssessment();
        $this->itemUuid = $this->seedAssignmentItem();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupAssignmentFixtures();
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'id' => self::RESULT_ID,
            'clientId' => $this->fxClientUuid,   // read by the insert code (validateCreateAccess)
            'client_id' => $this->fxClientUuid,  // required (v4) by AssessmentResultBlobValidator
            'data' => [
                '_assignmentItemId' => $this->itemUuid,
                '_assessment' => ['_version' => 1, '_uid' => $this->fxAssessmentUid],
                '_answers' => [],
                '_flaggedQuestions' => [],
                '_scaleResults' => [],
            ],
        ];
    }

    private function makeService(AssignmentCompleter $completer): AssessmentResponseBlobFHIRResourceService
    {
        $service = new AssessmentResponseBlobFHIRResourceService(
            new AssessmentResultRepository(),
            new AssignmentRepository(),
            $completer,
            new PatientService()
        );
        // production injects the logger via Symfony DI autowiring (#[Required] setLogger)
        $service->setLogger(new \OpenEMR\Common\Logging\SystemLogger());
        return $service;
    }

    private function insert(AssessmentResponseBlobFHIRResourceService $service, array $record): ProcessingResult
    {
        $m = new \ReflectionMethod($service, 'insertOpenEmrRecord');
        $m->setAccessible(true);
        return $m->invoke($service, $record);
    }

    public function testInsertPersistsResultAndMarksAssignmentComplete(): void
    {
        $completer = $this->createMock(AssignmentCompleter::class);
        // the completed assessment item is marked complete exactly once
        $completer->expects($this->once())
            ->method('markAssignmentComplete')
            ->with($this->isInstanceOf(AssignedAssessment::class), $this->isType('array'));

        $result = $this->insert($this->makeService($completer), $this->validPayload());

        $this->assertTrue($result->isValid(), 'insert should succeed: ' . json_encode($result->getValidationMessages()));
        $this->assertFalse($result->hasInternalErrors(), 'no internal errors');
        $this->assertTrue($result->hasData());

        // the result blob row was actually written for this patient
        $count = QueryUtils::fetchSingleValue(
            "SELECT COUNT(*) AS c FROM dac_AssessmentResultBlob WHERE client_id = ? AND assessment_id = ?",
            'c',
            [$this->fxPid, $this->fxAssessmentId]
        );
        $this->assertSame(1, (int) $count);
    }

    public function testInsertWithInvalidPayloadReturnsValidationErrorsAndDoesNotComplete(): void
    {
        $completer = $this->createMock(AssignmentCompleter::class);
        $completer->expects($this->never())->method('markAssignmentComplete');

        $payload = $this->validPayload();
        unset($payload['data']['_assessment']); // required subobject missing -> validation fails

        $result = $this->insert($this->makeService($completer), $payload);

        $this->assertFalse($result->isValid());
    }

    public function testInsertWithUnknownAssignmentItemReturnsInternalError(): void
    {
        $completer = $this->createMock(AssignmentCompleter::class);
        $completer->expects($this->never())->method('markAssignmentComplete');

        $payload = $this->validPayload();
        $payload['data']['_assignmentItemId'] = '00000000-0000-4000-8000-0000000000ff'; // valid v4, no such item

        $result = $this->insert($this->makeService($completer), $payload);

        $this->assertTrue($result->hasInternalErrors(), 'unknown assignment item -> handled as an internal error, not a crash');
    }
}
