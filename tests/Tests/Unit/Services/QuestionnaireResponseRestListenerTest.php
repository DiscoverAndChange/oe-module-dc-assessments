<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\QuestionnaireResponseRestListener;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetResultBlobFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unit coverage for QuestionnaireResponseRestListener's event routing:
 * dispatchFHIRInsertEvent (fhir.questionnaire_response.pre_insert) and
 * dispatchFHIRSearchEvent (fhir.questionnaire_response.search).
 *
 * Both constructor collaborators — AssessmentResponseBlobFHIRResourceService and
 * LibraryAssetResultBlobFHIRResourceService, which perform the real blob insert /
 * FHIR-server search work — are MOCKED with createMock (which also suppresses their
 * FhirServiceBase parent constructors), so no DB or document/PDF side effect runs.
 * The tests build a real FHIR QuestionnaireResponse carrying (or lacking) the DAC
 * extension and assert the listener routes the GenericEvent to the expected service.
 */
class QuestionnaireResponseRestListenerTest extends TestCase
{
    private AssessmentResponseBlobFHIRResourceService&MockObject $assessmentService;
    private LibraryAssetResultBlobFHIRResourceService&MockObject $libraryService;
    private QuestionnaireResponseRestListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentService = $this->createMock(AssessmentResponseBlobFHIRResourceService::class);
        $this->libraryService = $this->createMock(LibraryAssetResultBlobFHIRResourceService::class);
        $this->listener = new QuestionnaireResponseRestListener($this->assessmentService, $this->libraryService);
        $this->listener->setLogger(new NullLogger());
    }

    private function questionnaireResponseWithExtensionUrl(string $url): FHIRQuestionnaireResponse
    {
        $extension = new FHIRExtension();
        $extension->setUrl($url);
        $qr = new FHIRQuestionnaireResponse();
        $qr->addExtension($extension);
        return $qr;
    }

    private function dacUrl(string $code): string
    {
        return "https://www.discoverandchange.com/fhir/" . $code;
    }

    // --- dispatchFHIRInsertEvent ---------------------------------------------------

    public function testInsertRoutesAssessmentExtensionToAssessmentService(): void
    {
        $qr = $this->questionnaireResponseWithExtensionUrl(
            $this->dacUrl(AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT)
        );
        $result = new ProcessingResult();

        $this->assessmentService->expects($this->once())->method('insert')->with($qr)->willReturn($result);
        $this->libraryService->expects($this->never())->method('insert');

        $event = new GenericEvent($qr);
        $this->listener->dispatchFHIRInsertEvent($event);

        $this->assertTrue($event->isPropagationStopped(), 'a handled insert stops propagation');
        $this->assertSame($result, $event->getArgument('result'));
    }

    /**
     * REGRESSION (fixed v0.12.9): array_filter() preserves keys, so when a non-DAC extension
     * precedes the DAC one the match is not at index 0. The old `$extension[0]` then missed it
     * and routing silently failed. The listener now reindexes before taking the first match.
     */
    public function testInsertRoutesWhenDacExtensionIsNotFirst(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $other = new FHIRExtension();
        $other->setUrl('https://example.com/some-other-extension'); // non-DAC, at index 0
        $qr->addExtension($other);
        $dac = new FHIRExtension();
        $dac->setUrl($this->dacUrl(AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT));
        $qr->addExtension($dac); // DAC match at index 1

        $result = new ProcessingResult();
        $this->assessmentService->expects($this->once())->method('insert')->with($qr)->willReturn($result);
        $this->libraryService->expects($this->never())->method('insert');

        $event = new GenericEvent($qr);
        $this->listener->dispatchFHIRInsertEvent($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame($result, $event->getArgument('result'));
    }

    public function testInsertRoutesLibraryAssetExtensionToLibraryService(): void
    {
        $qr = $this->questionnaireResponseWithExtensionUrl(
            $this->dacUrl(LibraryAssetResultBlobFHIRResourceService::CODE_DAC_LIBRARY_ASSET)
        );
        $result = new ProcessingResult();

        $this->libraryService->expects($this->once())->method('insert')->with($qr)->willReturn($result);
        $this->assessmentService->expects($this->never())->method('insert');

        $event = new GenericEvent($qr);
        $this->listener->dispatchFHIRInsertEvent($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame($result, $event->getArgument('result'));
    }

    public function testInsertWithNonDacExtensionIsNotRouted(): void
    {
        // extension URL does not match the DAC "openemr-" prefix -> filtered out
        $qr = $this->questionnaireResponseWithExtensionUrl('https://example.com/fhir/some-other-extension');

        $this->assessmentService->expects($this->never())->method('insert');
        $this->libraryService->expects($this->never())->method('insert');

        $event = new GenericEvent($qr);
        $this->listener->dispatchFHIRInsertEvent($event);

        $this->assertFalse($event->isPropagationStopped());
        $this->assertFalse($event->hasArgument('result'), 'no result argument set when nothing matched');
    }

    public function testInsertWithNonQuestionnaireResponseSubjectIsIgnored(): void
    {
        $this->assessmentService->expects($this->never())->method('insert');
        $this->libraryService->expects($this->never())->method('insert');

        $event = new GenericEvent('not-a-fhir-resource');
        $this->listener->dispatchFHIRInsertEvent($event);

        $this->assertFalse($event->isPropagationStopped());
        $this->assertFalse($event->hasArgument('result'));
    }

    // --- dispatchFHIRSearchEvent ---------------------------------------------------

    /**
     * Stub each blob sub-service's declared search fields. array_intersect_key only looks at the
     * keys, so the values are irrelevant (the real return is FhirSearchParameterDefinition[]). Both
     * blob services support _id, title and (for patient scoping) patient.
     *
     * @return array<string, true>
     */
    private function blobSupportedParams(): array
    {
        return ['_id' => true, 'title' => true, 'patient' => true];
    }

    public function testSearchAggregatesBothServicesWhenValid(): void
    {
        $searchParams = ['_id' => 'abc'];

        $this->assessmentService->method('getSearchParams')->willReturn($this->blobSupportedParams());
        $this->libraryService->method('getSearchParams')->willReturn($this->blobSupportedParams());

        // _id is supported, so it is forwarded unchanged; no puuidBind on the event -> null.
        $this->assessmentService->expects($this->once())->method('getAll')->with($searchParams, null)
            ->willReturn(new ProcessingResult());
        $this->libraryService->expects($this->once())->method('getAll')->with($searchParams, null)
            ->willReturn(new ProcessingResult());

        $event = new GenericEvent($searchParams);
        $returned = $this->listener->dispatchFHIRSearchEvent($event);

        $this->assertSame($event, $returned);
        $result = $event->getArgument('result');
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * The parent QuestionnaireResponse resource supports fields (e.g. `authored`) the blob sub-services
     * do not; forwarding an unknown field makes createOpenEMRSearchParameters() throw SearchFieldException
     * (which surfaced as a 400 on QuestionnaireResponse create). The listener strips fields a sub-service
     * does not declare, while keeping the ones it does (here `_id` and `patient`).
     */
    public function testSearchDropsParametersTheBlobServicesDoNotSupport(): void
    {
        $this->assessmentService->method('getSearchParams')->willReturn($this->blobSupportedParams());
        $this->libraryService->method('getSearchParams')->willReturn($this->blobSupportedParams());

        // `authored` is unsupported and dropped; `_id` and `patient` are kept.
        $expected = ['_id' => 'abc', 'patient' => 'puuid-123'];
        $this->assessmentService->expects($this->once())->method('getAll')->with($expected, null)
            ->willReturn(new ProcessingResult());
        $this->libraryService->expects($this->once())->method('getAll')->with($expected, null)
            ->willReturn(new ProcessingResult());

        $event = new GenericEvent(['_id' => 'abc', 'patient' => 'puuid-123', 'authored' => '2026-01-01']);
        $this->listener->dispatchFHIRSearchEvent($event);

        $result = $event->getArgument('result');
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * SECURITY: in a patient context the bound patient uuid must reach the blob sub-services so their
     * results are scoped to that patient (both are IPatientCompartmentResourceService). The parent
     * QuestionnaireResponse resource forwards its $puuidBind on the event; the listener must pass it
     * through to each sub-service's getAll() as the second argument.
     */
    public function testSearchForwardsPuuidBindToSubServices(): void
    {
        $this->assessmentService->method('getSearchParams')->willReturn($this->blobSupportedParams());
        $this->libraryService->method('getSearchParams')->willReturn($this->blobSupportedParams());

        $puuid = 'patient-uuid-xyz';
        $this->assessmentService->expects($this->once())->method('getAll')->with(['_id' => 'abc'], $puuid)
            ->willReturn(new ProcessingResult());
        $this->libraryService->expects($this->once())->method('getAll')->with(['_id' => 'abc'], $puuid)
            ->willReturn(new ProcessingResult());

        $event = new GenericEvent(['_id' => 'abc'], ['puuidBind' => $puuid]);
        $this->listener->dispatchFHIRSearchEvent($event);

        $result = $event->getArgument('result');
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    public function testSearchMergesDataFromBothServicesIntoAggregateResult(): void
    {
        $searchParams = ['_id' => 'abc'];

        $this->assessmentService->method('getSearchParams')->willReturn($this->blobSupportedParams());
        $this->libraryService->method('getSearchParams')->willReturn($this->blobSupportedParams());

        $assessmentResult = new ProcessingResult();
        $assessmentResult->addData(['source' => 'assessment']);
        $libraryResult = new ProcessingResult();
        $libraryResult->addData(['source' => 'library']);

        $this->assessmentService->expects($this->once())->method('getAll')->willReturn($assessmentResult);
        $this->libraryService->expects($this->once())->method('getAll')->willReturn($libraryResult);

        $event = new GenericEvent($searchParams);
        $this->listener->dispatchFHIRSearchEvent($event);

        $result = $event->getArgument('result');
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isValid());
        // both services' data is aggregated into the single returned ProcessingResult
        $this->assertCount(2, (array) $result->getData());
    }
}
