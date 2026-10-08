<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\QuestionnaireResponseFormFHIRResourceService;
use OpenEMR\Services\FHIR\UtilsService;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes QuestionnaireResponseFormFHIRResourceService::parseFhirResource, the
 * write-direction mapping of an inbound FHIR QuestionnaireResponse into the OpenEMR
 * record the create path (insertOpenEMRRecord) consumes.
 *
 * parseFhirResource only uses UtilsService::parseReference / parseCanonicalUrl (pure
 * string parsing, no DB), so it runs as a unit test. Guards three bugs fixed in v0.12.3.
 */
class QuestionnaireResponseFormFHIRResourceServiceTest extends TestCase
{
    private function parse(FHIRQuestionnaireResponse $qr): array
    {
        $service = new QuestionnaireResponseFormFHIRResourceService();
        return $service->parseFhirResource($qr);
    }

    /**
     * Happy path: a Patient subject, an Encounter reference and a Practitioner source
     * all map to their respective parsed keys using THEIR OWN uuids.
     *
     * Regression: the Encounter block previously wrote into a throwaway local var using
     * the RESPONSE's uuid, so encounter_uuid was never populated and the submitted
     * Encounter reference was silently dropped.
     */
    public function testParsesPatientEncounterAndPractitionerReferences(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $qr->setSubject(UtilsService::createRelativeReference('Patient', 'patient-uuid'));
        $qr->setEncounter(UtilsService::createRelativeReference('Encounter', 'encounter-uuid'));
        $qr->setSource(UtilsService::createRelativeReference('Practitioner', 'practitioner-uuid'));

        $parsed = $this->parse($qr);

        $this->assertSame('patient-uuid', $parsed['puuid']);
        $this->assertSame('encounter-uuid', $parsed['encounter_uuid']);
        $this->assertSame('practitioner-uuid', $parsed['creator_user_uuid']);
    }

    /**
     * Regression: `!empty($ref['type']) == 'Patient'` binds as `(true) == 'Patient'`,
     * which is always true for any non-empty reference, so a non-Patient subject (e.g.
     * Organization) was mis-stored as the patient. Now the type is actually checked.
     */
    public function testNonPatientSubjectDoesNotSetPuuid(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $qr->setSubject(UtilsService::createRelativeReference('Organization', 'org-uuid'));

        $parsed = $this->parse($qr);

        $this->assertArrayNotHasKey('puuid', $parsed);
    }

    /**
     * Regression (same precedence bug on the source block): a Patient-authored response
     * must not be recorded as having a Practitioner creator.
     */
    public function testPatientSourceDoesNotSetCreatorUserUuid(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $qr->setSource(UtilsService::createRelativeReference('Patient', 'patient-uuid'));

        $parsed = $this->parse($qr);

        $this->assertArrayNotHasKey('creator_user_uuid', $parsed);
    }

    /**
     * parseOpenEMRRecord (read direction) builds a FHIR QuestionnaireResponse from a DB record.
     * Assertions go through json_encode so they are robust to the FHIR getters' wrapper shapes.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function parseOut(array $record): array
    {
        $service = new QuestionnaireResponseFormFHIRResourceService();
        return json_decode((string) json_encode($service->parseOpenEMRRecord($record, false)), true);
    }

    public function testParseOpenEMRRecordPatientAuthored(): void
    {
        $out = $this->parseOut([
            'questionnaire_response' => '{}',
            'version' => '2',
            'questionnaire_response_uuid' => 'qr-uuid',
            'questionnaire_id' => 'q-uuid',
            'encounter_uuid' => 'enc-uuid',
            'puuid' => 'pat-uuid',
            'creator_user_id' => null,   // empty -> source is the Patient
            'create_time' => '2026-03-04 05:06:07',
            'status' => 'completed',
        ]);

        $this->assertSame('qr-uuid', $out['id']);
        $this->assertSame('completed', $out['status']);
        $this->assertStringContainsString('Patient/pat-uuid', $out['subject']['reference']);
        $this->assertStringContainsString('Patient/pat-uuid', $out['source']['reference']);
        $this->assertStringContainsString('Encounter/enc-uuid', $out['encounter']['reference']);
        $this->assertStringContainsString('Questionnaire/q-uuid', $out['questionnaire']);
        $this->assertNotEmpty($out['authored']);
    }

    public function testParseOpenEMRRecordPractitionerAuthoredMapsIncompleteStatus(): void
    {
        $out = $this->parseOut([
            'questionnaire_response' => '{}',
            'questionnaire_response_uuid' => 'qr-2',
            'puuid' => 'pat-2',
            'creator_user_id' => 7,              // non-empty -> source is the Practitioner
            'creator_user_uuid' => 'prac-uuid',
            'status' => 'incomplete',            // maps to in-progress
        ]);

        $this->assertStringContainsString('Practitioner/prac-uuid', $out['source']['reference']);
        $this->assertSame('in-progress', $out['status']);
    }
}
