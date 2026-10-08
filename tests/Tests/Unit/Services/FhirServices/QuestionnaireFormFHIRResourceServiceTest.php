<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\QuestionnaireFormFHIRResourceService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for QuestionnaireFormFHIRResourceService: supportsCode and the
 * read-direction parseOpenEMRRecord mapping (an OpenEMR questionnaire record whose
 * `questionnaire` column holds FHIR json -> FHIRQuestionnaire).
 *
 * supportsCode here unconditionally returns true (the service claims to support any
 * LOINC code and has no CODE constant of its own), so both branches are characterized
 * as true rather than asserting a false case.
 */
class QuestionnaireFormFHIRResourceServiceTest extends TestCase
{
    private function service(): QuestionnaireFormFHIRResourceService
    {
        // ctor only takes an optional fhir api url; it news up a QuestionnaireService
        // internally (construction only, no query) so nothing to mock.
        return new QuestionnaireFormFHIRResourceService(null);
    }

    public function testSupportsCodeAlwaysTrue(): void
    {
        $service = $this->service();
        // documents that this service supports any code (LOINC pass-through)
        $this->assertTrue($service->supportsCode('44249-1'));
        $this->assertTrue($service->supportsCode('foo'));
    }

    public function testParseOpenEMRRecordHydratesFromQuestionnaireJson(): void
    {
        $questionnaireJson = json_encode([
            'resourceType' => 'Questionnaire',
            'status' => 'active',
            'title' => 'PHQ-9',
        ]);
        $record = [
            'uuid' => 'questionnaire-uuid-789',
            'version' => '5',
            'source_url' => 'https://example.com/fhir/Questionnaire/phq9',
            'questionnaire' => $questionnaireJson,
        ];

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRQuestionnaire::class, $fhir);
        $this->assertSame('questionnaire-uuid-789', $fhir->getId()->getValue());
        $this->assertSame('5', $fhir->getMeta()->getVersionId());
        // source_url overrides the resource url with the raw stored string
        $this->assertSame(
            'https://example.com/fhir/Questionnaire/phq9',
            $fhir->getUrl()
        );
    }

    public function testParseOpenEMRRecordWithInvalidJsonStillProducesResource(): void
    {
        // invalid questionnaire json is logged and skipped; the record id/meta must
        // still map so the resource is well-formed.
        $record = [
            'uuid' => 'questionnaire-uuid-bad',
            'questionnaire' => '{not valid json',
        ];

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRQuestionnaire::class, $fhir);
        $this->assertSame('questionnaire-uuid-bad', $fhir->getId()->getValue());
        $this->assertSame('1', $fhir->getMeta()->getVersionId());
    }
}
