<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentFHIRResourceService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for AssessmentFHIRResourceService: supportsCode and the read-direction
 * parseOpenEMRRecord mapping (an OpenEMR assessment record -> FHIRQuestionnaire).
 *
 * parseOpenEMRRecord is a pure data->FHIR conversion; the repository is mocked and never
 * touched. createProvenanceResource / search are not exercised (they need the DB-backed
 * repo and FhirProvenanceService) and are left for the integration suite.
 */
class AssessmentFHIRResourceServiceTest extends TestCase
{
    private function service(): AssessmentFHIRResourceService
    {
        $repo = $this->createMock(AssessmentRepository::class);
        return new AssessmentFHIRResourceService($repo);
    }

    public function testSupportsCode(): void
    {
        $service = $this->service();
        $this->assertTrue($service->supportsCode(AssessmentFHIRResourceService::CODE_DAC_ASSESSMENT));
        $this->assertFalse($service->supportsCode('foo'));
    }

    public function testParseOpenEMRRecordPublicMapsToActive(): void
    {
        $record = [
            'uuid' => 'assessment-uuid-123',
            'version' => '3',
            'uid' => 'my-computable-name',
            'name' => 'My Assessment Title',
            'isPublic' => 1,
            'data' => '{"foo":"bar"}',
        ];

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRQuestionnaire::class, $fhir);
        $this->assertSame('assessment-uuid-123', $fhir->getId()->getValue());
        // name/title/status are stored as the raw strings the service passes in
        $this->assertSame('my-computable-name', $fhir->getName());
        $this->assertSame('My Assessment Title', $fhir->getTitle());
        $this->assertSame('active', $fhir->getStatus());
        $this->assertSame('3', $fhir->getMeta()->getVersionId());

        // the service's code is attached as a FHIRCode
        $codes = $fhir->getCode();
        $this->assertNotEmpty($codes);
        $this->assertSame(
            AssessmentFHIRResourceService::CODE_DAC_ASSESSMENT,
            $codes[0]->getValue()
        );

        // the raw assessment json is carried in an extension keyed by the DAC code
        $extensions = $fhir->getExtension();
        $this->assertNotEmpty($extensions);
        $this->assertStringContainsString(
            AssessmentFHIRResourceService::CODE_DAC_ASSESSMENT,
            $extensions[0]->getUrl()
        );
        $this->assertSame('{"foo":"bar"}', $extensions[0]->getValueString());
    }

    public function testParseOpenEMRRecordNonPublicMapsToDraft(): void
    {
        $record = [
            'uuid' => 'assessment-uuid-456',
            'isPublic' => 0,
            'name' => 'Draft',
        ];

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertSame('draft', $fhir->getStatus());
        // version defaults to '1' when absent
        $this->assertSame('1', $fhir->getMeta()->getVersionId());
    }
}
