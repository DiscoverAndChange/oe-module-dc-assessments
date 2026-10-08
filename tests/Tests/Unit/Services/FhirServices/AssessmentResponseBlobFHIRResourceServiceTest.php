<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for AssessmentResponseBlobFHIRResourceService: supportsCode, the
 * read-direction parseOpenEMRRecord mapping, and the write-direction parseFhirResource
 * mapping (inbound FHIRQuestionnaireResponse -> OpenEMR record array).
 *
 * All four constructor deps are mocked; parseFhirResource uses only UtilsService helpers
 * (pure string parsing of extensions/references, no DB). insert()/insertOpenEmrRecord()
 * require a live transaction, session and repositories and are left to the integration
 * suite.
 */
class AssessmentResponseBlobFHIRResourceServiceTest extends TestCase
{
    private function dacExtensionUrl(): string
    {
        return "https://www.discoverandchange.com/fhir/"
            . AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT;
    }

    private function service(): AssessmentResponseBlobFHIRResourceService
    {
        return new AssessmentResponseBlobFHIRResourceService(
            $this->createMock(AssessmentResultRepository::class),
            $this->createMock(AssignmentRepository::class),
            $this->createMock(AssignmentCompleter::class),
            $this->createMock(PatientService::class)
        );
    }

    public function testSupportsCode(): void
    {
        $service = $this->service();
        $this->assertTrue($service->supportsCode(AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT));
        $this->assertFalse($service->supportsCode('foo'));
    }

    public function testParseOpenEMRRecord(): void
    {
        $record = [
            '_id' => 'result-uuid-1',
            'version' => '2',
            'uid' => 'computable',
            'name' => 'Response Title',
            'data' => ['answers' => ['q1' => 'yes']],
        ];

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRQuestionnaire::class, $fhir);
        $this->assertSame('result-uuid-1', $fhir->getId()->getValue());
        $this->assertSame('computable', $fhir->getName());
        $this->assertSame('Response Title', $fhir->getTitle());
        $this->assertSame('active', $fhir->getStatus());
        $this->assertSame('2', $fhir->getMeta()->getVersionId());

        $codes = $fhir->getCode();
        $this->assertNotEmpty($codes);
        $this->assertSame(
            AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT,
            $codes[0]->getValue()
        );

        // the whole record is json-encoded into the DAC extension
        $extensions = $fhir->getExtension();
        $this->assertNotEmpty($extensions);
        $this->assertSame($this->dacExtensionUrl(), $extensions[0]->getUrl());
        $decoded = json_decode($extensions[0]->getValueString(), true);
        $this->assertSame('result-uuid-1', $decoded['_id']);
        $this->assertSame(['q1' => 'yes'], $decoded['data']['answers']);
    }

    public function testParseFhirResourceExtractsRecordAndClientId(): void
    {
        $payload = ['data' => ['_assignmentItemId' => 'item-1'], 'answers' => ['a' => 1]];
        $extension = new FHIRExtension();
        $extension->setUrl($this->dacExtensionUrl());
        $extension->setValueString(json_encode($payload));

        $qr = new FHIRQuestionnaireResponse();
        $qr->addExtension($extension);
        $qr->setAuthor(UtilsService::createRelativeReference('Patient', 'client-uuid-9'));

        $parsed = $this->service()->parseFhirResource($qr);

        $this->assertIsArray($parsed);
        $this->assertSame('item-1', $parsed['data']['_assignmentItemId']);
        $this->assertSame(['a' => 1], $parsed['answers']);
        // the author reference uuid becomes the clientId on the parsed record
        $this->assertSame('client-uuid-9', $parsed['clientId']);
    }

    public function testParseFhirResourceReturnsNullWhenNoDacExtension(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $qr->setAuthor(UtilsService::createRelativeReference('Patient', 'client-uuid-9'));

        $this->assertNull($this->service()->parseFhirResource($qr));
    }

    public function testParseFhirResourceRejectsWrongResourceType(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->service()->parseFhirResource(new FHIRQuestionnaire());
    }
}
