<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Services;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Pure-unit (no DB) tests for the QuestionnaireResponseFHIRResourceService.
 *
 * This service fires a Symfony event (fhir.questionnaire_response.search) before fanning
 * a search out to its mapped sub-services. We mock the EventDispatcher so the event-driven
 * short-circuit paths of getAll() can be exercised without a listener or a DB. The search
 * parameter definitions are read through reflection (loadSearchParameters is protected).
 */
class QuestionnaireResponseFHIRResourceServiceTest extends TestCase
{
    private function makeService(EventDispatcher $dispatcher): QuestionnaireResponseFHIRResourceService
    {
        return new QuestionnaireResponseFHIRResourceService($dispatcher);
    }

    /**
     * loadSearchParameters() is protected; invoke it via reflection and assert the FHIR
     * QuestionnaireResponse search keys the service advertises are present.
     */
    public function testLoadSearchParametersDeclaresExpectedKeys(): void
    {
        $service = $this->makeService($this->createMock(EventDispatcher::class));

        $method = new \ReflectionMethod($service, 'loadSearchParameters');
        $method->setAccessible(true);
        $params = $method->invoke($service);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('_id', $params);
        $this->assertArrayHasKey('questionnaire', $params);
        $this->assertArrayHasKey('patient', $params);
        $this->assertArrayHasKey('authored', $params);
    }

    /**
     * When a listener stops event propagation, getAll() returns the ProcessingResult the
     * event carries and never fans out to the mapped sub-services.
     */
    public function testGetAllReturnsEventResultWhenPropagationStopped(): void
    {
        $expected = new ProcessingResult();
        $expected->addData(['sentinel' => true]);

        $event = new GenericEvent(['patient' => 'puuid'], ['result' => $expected]);
        $event->stopPropagation();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($event);

        $service = $this->makeService($dispatcher);
        $result = $service->getAll(['patient' => 'puuid']);

        $this->assertSame($expected, $result);
    }

    /**
     * When the event's ProcessingResult is invalid (has validation messages), getAll()
     * returns it immediately without fanning out to the mapped sub-services.
     */
    public function testGetAllReturnsEventResultWhenResultInvalid(): void
    {
        $expected = new ProcessingResult();
        $expected->setValidationMessages(['patient' => 'bad reference']);
        $this->assertFalse($expected->isValid(), 'precondition: result must be invalid');

        $event = new GenericEvent(['patient' => 'puuid'], ['result' => $expected]);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($event);

        $service = $this->makeService($dispatcher);
        $result = $service->getAll(['patient' => 'puuid']);

        $this->assertSame($expected, $result);
        $this->assertFalse($result->isValid());
    }
}
