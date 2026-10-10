<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\FhirServices;

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetResultBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for LibraryAssetResultBlobFHIRResourceService: supportsCode, the
 * write-direction parseFhirResource mapping (inbound FHIRQuestionnaireResponse ->
 * OpenEMR record array) and the read-direction parseOpenEMRRecord mapping
 * (record -> FHIRQuestionnaireResponse carrying the json blob).
 *
 * All four constructor deps are mocked. parseFhirResource uses only UtilsService helpers
 * (pure, no DB). insert()/insertOpenEmrRecord() need a live transaction, session and the
 * repositories so they are left to the integration suite.
 */
class LibraryAssetResultBlobFHIRResourceServiceTest extends TestCase
{
    private function dacExtensionUrl(): string
    {
        return "https://www.discoverandchange.com/fhir/"
            . LibraryAssetResultBlobFHIRResourceService::CODE_DAC_LIBRARY_ASSET;
    }

    private function service(): LibraryAssetResultBlobFHIRResourceService
    {
        return new LibraryAssetResultBlobFHIRResourceService(
            $this->createMock(LibraryAssetResultBlobRepository::class),
            $this->createMock(PatientService::class),
            $this->createMock(AssignmentCompleter::class),
            $this->createMock(AssignmentRepository::class)
        );
    }

    public function testSupportsCode(): void
    {
        $service = $this->service();
        $this->assertTrue($service->supportsCode(LibraryAssetResultBlobFHIRResourceService::CODE_DAC_LIBRARY_ASSET));
        $this->assertFalse($service->supportsCode('foo'));
    }

    /**
     * SECURITY: library-asset result blobs are per-patient data, so when the listener forwards a bound
     * patient uuid this service must scope to it. It therefore declares IPatientCompartmentResourceService
     * and maps the patient search field to the patient_uuid column
     * (LibraryAssetResultBlobRepository::search exposes patient_data.uuid AS patient_uuid).
     */
    public function testIsPatientCompartmentScopedToPatientUuid(): void
    {
        $service = $this->service();
        $this->assertInstanceOf(
            \OpenEMR\Services\FHIR\IPatientCompartmentResourceService::class,
            $service
        );

        $field = $service->getPatientContextSearchField();
        $this->assertSame('patient', $field->getName());
        $mapped = array_map(
            static fn($serviceField) => $serviceField->getField(),
            $field->getMappedFields()
        );
        $this->assertSame(['patient_uuid'], $mapped);
    }

    public function testParseFhirResourceExtractsRecordAndClientId(): void
    {
        $payload = ['id' => 'blob-result-1', 'answers' => ['q1' => 'ok'], 'journal' => 'notes'];
        $extension = new FHIRExtension();
        $extension->setUrl($this->dacExtensionUrl());
        $extension->setValueString(json_encode($payload));

        $qr = new FHIRQuestionnaireResponse();
        $qr->addExtension($extension);
        $qr->setAuthor(UtilsService::createRelativeReference('Patient', 'client-uuid-42'));

        $parsed = $this->service()->parseFhirResource($qr);

        $this->assertIsArray($parsed);
        $this->assertSame('blob-result-1', $parsed['id']);
        $this->assertSame(['q1' => 'ok'], $parsed['answers']);
        $this->assertSame('notes', $parsed['journal']);
        $this->assertSame('client-uuid-42', $parsed['clientId']);
    }

    public function testParseFhirResourceReturnsNullWhenNoDacExtension(): void
    {
        $qr = new FHIRQuestionnaireResponse();
        $qr->setAuthor(UtilsService::createRelativeReference('Patient', 'client-uuid-42'));

        $this->assertNull($this->service()->parseFhirResource($qr));
    }

    public function testParseFhirResourceRejectsWrongResourceType(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->service()->parseFhirResource(new FHIRQuestionnaire());
    }

    public function testParseOpenEMRRecord(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->setId('blob-result-uuid-7');
        $dto->setAnswers(['q1' => 'yes']);
        $dto->setJournal('a journal entry');
        $record = $dto->jsonSerialize();

        $fhir = $this->service()->parseOpenEMRRecord($record);

        $this->assertInstanceOf(FHIRQuestionnaireResponse::class, $fhir);
        $this->assertSame('blob-result-uuid-7', $fhir->getId()->getValue());
        // version defaults to '1' (DTO carries no version key)
        $this->assertSame('1', $fhir->getMeta()->getVersionId());

        // the serialized record is carried json-encoded in the DAC extension
        $extensions = $fhir->getExtension();
        $this->assertNotEmpty($extensions);
        $this->assertSame($this->dacExtensionUrl(), $extensions[0]->getUrl());
        $decoded = json_decode($extensions[0]->getValueString(), true);
        $this->assertSame('blob-result-uuid-7', $decoded['id']);
        $this->assertSame(['q1' => 'yes'], $decoded['answers']);
        $this->assertSame('a journal entry', $decoded['journal']);
    }
}
