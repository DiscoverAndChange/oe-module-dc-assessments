<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Services;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\QuestionnaireFormFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) tests for the QuestionnaireFHIRResourceService delegation service.
 *
 * This service extends OpenEMR's FhirServiceBase and routes a FHIR Questionnaire search
 * to one of three mapped sub-services (Assessment, QuestionnaireForm, LibraryAsset) based
 * on the questionnaire-code token. All collaborators are mocked so no DB is touched.
 */
class QuestionnaireFHIRResourceServiceTest extends TestCase
{
    /**
     * @return array{0: QuestionnaireFHIRResourceService, 1: AssessmentFHIRResourceService&\PHPUnit\Framework\MockObject\MockObject, 2: QuestionnaireFormFHIRResourceService&\PHPUnit\Framework\MockObject\MockObject, 3: LibraryAssetFHIRResourceService&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function makeService(): array
    {
        $assessment = $this->createMock(AssessmentFHIRResourceService::class);
        $questionnaireForm = $this->createMock(QuestionnaireFormFHIRResourceService::class);
        $libraryAsset = $this->createMock(LibraryAssetFHIRResourceService::class);
        $service = new QuestionnaireFHIRResourceService($assessment, $questionnaireForm, $libraryAsset);
        return [$service, $assessment, $questionnaireForm, $libraryAsset];
    }

    /**
     * loadSearchParameters() is protected; invoke it via reflection and assert the
     * FHIR Questionnaire search keys the service advertises are present.
     */
    public function testLoadSearchParametersDeclaresExpectedKeys(): void
    {
        [$service] = $this->makeService();

        $method = new \ReflectionMethod($service, 'loadSearchParameters');
        $method->setAccessible(true);
        $params = $method->invoke($service);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('_id', $params);
        $this->assertArrayHasKey('title', $params);
        $this->assertArrayHasKey('questionnaire-code', $params);
    }

    /**
     * With a questionnaire-code parameter present, getAll() resolves the mapped
     * sub-service whose supportsCode() returns true (via getServiceForCode) and delegates
     * the search to it, returning that sub-service's ProcessingResult unchanged.
     */
    public function testGetAllWithQuestionnaireCodeDelegatesToSupportingService(): void
    {
        [$service, $assessment, $questionnaireForm, $libraryAsset] = $this->makeService();

        $expected = new ProcessingResult();
        $expected->addData(['sentinel' => true]);

        // Only the QuestionnaireForm sub-service claims the code.
        $assessment->method('supportsCode')->willReturn(false);
        $libraryAsset->method('supportsCode')->willReturn(false);
        $questionnaireForm->method('supportsCode')->willReturn(true);

        // The supporting service is the only one asked for data.
        $assessment->expects($this->never())->method('getAll');
        $libraryAsset->expects($this->never())->method('getAll');
        $questionnaireForm->expects($this->once())
            ->method('getAll')
            ->willReturn($expected);

        $result = $service->getAll(['questionnaire-code' => 'my-code']);

        $this->assertSame($expected, $result);
    }

    /**
     * Without a questionnaire-code parameter, getAll() falls through to searchAllServices,
     * which fans the search out to every mapped sub-service's getAll().
     */
    public function testGetAllWithoutCodeSearchesAllMappedServices(): void
    {
        [$service, $assessment, $questionnaireForm, $libraryAsset] = $this->makeService();

        $params = ['title' => 'Depression'];

        $assessment->expects($this->once())
            ->method('getAll')
            ->with($params, null)
            ->willReturn(new ProcessingResult());
        $questionnaireForm->expects($this->once())
            ->method('getAll')
            ->with($params, null)
            ->willReturn(new ProcessingResult());
        $libraryAsset->expects($this->once())
            ->method('getAll')
            ->with($params, null)
            ->willReturn(new ProcessingResult());

        $result = $service->getAll($params);

        $this->assertInstanceOf(ProcessingResult::class, $result);
    }

    /**
     * createProvenanceResource() rejects a record that is not a FHIRQuestionnaire by
     * throwing a BadMethodCallException.
     */
    public function testCreateProvenanceResourceWithWrongTypeThrows(): void
    {
        [$service] = $this->makeService();

        $this->expectException(\BadMethodCallException::class);
        $service->createProvenanceResource(new FHIRQuestionnaireResponse());
    }

    /**
     * REGRESSION (fixed v0.12.5): an unsupported questionnaire-code makes getServiceForCode()
     * throw OpenEMR\Services\Search\SearchFieldException. getAll()'s catch previously named an
     * unimported SearchFieldException (resolving to a nonexistent module-namespaced class), so
     * the real exception escaped uncaught. It is now caught and surfaced as validation messages.
     */
    public function testGetAllWithUnsupportedCodeIsCaughtAsValidationMessages(): void
    {
        [$service, $assessment, $questionnaireForm, $libraryAsset] = $this->makeService();
        $assessment->method('supportsCode')->willReturn(false);
        $questionnaireForm->method('supportsCode')->willReturn(false);
        $libraryAsset->method('supportsCode')->willReturn(false);

        $result = $service->getAll(['questionnaire-code' => 'no-such-code']);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertFalse($result->isValid(), 'unsupported code should surface as validation messages, not an uncaught exception');
    }
}
