<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use Nyholm\Psr7\Stream;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireResponseRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for QuestionnaireResponseRestController::create (plus the trivial
 * update() and one()-not-found paths), the FHIR routing/response logic the module
 * owner relies on when the patient SPA POSTs a QuestionnaireResponse.
 *
 * The single constructor dependency — QuestionnaireResponseFHIRResourceService (the
 * resourceService, which does the real DB insert / FHIR-server work) — is MOCKED with
 * createMock, so no DB is touched (createMock also suppresses its parent FhirServiceBase
 * constructor). The request is a real ServerRestRequest wrapping a mocked HttpRestRequest
 * whose getHeader('Prefer') and getBody() (a Nyholm stream of the raw JSON) the controller
 * reads directly via getBody()->getContents().
 *
 * create() branches covered:
 *   - resourceService->insert() returns an INVALID ProcessingResult
 *       -> getFhirCreateResponseForProcessingResult -> 400 (non-201).
 *   - malformed body ({"data": <non-array>}) makes decodeRequest's FHIR hydration throw
 *       \InvalidArgumentException -> catch -> 400 + OperationOutcome body.
 *   - resourceService->insert() throws a generic \Exception -> catch -> 500 + OperationOutcome.
 *
 * DEFERRED: the 201 "representation" happy path calls $this->one() (a DB read via
 * resourceService->getOne()) whose returned record must round-trip through RestUtils; it
 * cannot be satisfied without a seeded/stubbed read model, so it is not exercised here. The
 * reachable one()-not-found path (empty ProcessingResult -> 404) IS covered instead.
 */
class QuestionnaireResponseRestControllerCreateTest extends TestCase
{
    /**
     * @param array<int, string> $prefer values returned for getHeader('Prefer')
     */
    private function makeRequest(string $json, array $prefer = []): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getHeader')->willReturn($prefer);
        $inner->method('getBody')->willReturn(Stream::create($json));
        $inner->method('getPatientUUIDString')->willReturn(null);
        return new ServerRestRequest($inner);
    }

    private function controller(QuestionnaireResponseFHIRResourceService $service): QuestionnaireResponseRestController
    {
        return new QuestionnaireResponseRestController($service);
    }

    public function testCreateWithInvalidProcessingResultReturnsNon201FhirCreateResponse(): void
    {
        $invalid = new ProcessingResult();
        $invalid->setValidationMessages(['questionnaire' => ['required']]); // isValid() == false

        $service = $this->createMock(QuestionnaireResponseFHIRResourceService::class);
        $service->expects($this->once())->method('insert')->willReturn($invalid);

        // valid (empty) FHIR body so decodeRequest succeeds and insert() is reached
        $response = $this->controller($service)->create($this->makeRequest('{}'));

        $this->assertNotSame(201, $response->getStatusCode(), 'an invalid result must not be a 201 create');
        $this->assertSame(400, $response->getStatusCode(), 'validation-only failure maps to 400');
    }

    public function testCreateWithMalformedBodyReturns400OperationOutcome(): void
    {
        $service = $this->createMock(QuestionnaireResponseFHIRResourceService::class);
        // decode throws before insert() is ever reached
        $service->expects($this->never())->method('insert');

        // {"data": <non-array>} -> FHIRQuestionnaireResponse ctor throws \InvalidArgumentException
        $response = $this->controller($service)->create($this->makeRequest('{"data":"not-an-array"}'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('OperationOutcome', (string) $response->getBody());
    }

    public function testCreateWhenInsertThrowsGenericExceptionReturns500OperationOutcome(): void
    {
        $service = $this->createMock(QuestionnaireResponseFHIRResourceService::class);
        $service->expects($this->once())->method('insert')
            ->willThrowException(new \Exception('boom from resource service'));

        $response = $this->controller($service)->create($this->makeRequest('{}'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('OperationOutcome', (string) $response->getBody());
    }

    public function testUpdateReturnsNotFound(): void
    {
        $service = $this->createMock(QuestionnaireResponseFHIRResourceService::class);
        $response = $this->controller($service)->update($this->makeRequest('{}'), 'any-id');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testOneReturnsNotFoundForEmptyResult(): void
    {
        $empty = new ProcessingResult(); // valid, but carries no data -> 404
        $service = $this->createMock(QuestionnaireResponseFHIRResourceService::class);
        $service->expects($this->once())->method('getOne')->willReturn($empty);

        $response = $this->controller($service)->one($this->makeRequest('{}'), 'missing-id');

        $this->assertSame(404, $response->getStatusCode());
    }
}
