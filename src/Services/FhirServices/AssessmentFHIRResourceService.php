<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRProvenance;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Services\FHIR\FhirProvenanceService;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Validators\ProcessingResult;

class AssessmentFHIRResourceService extends FhirServiceBase
{
    use FhirServiceBaseEmptyTrait;

    const CODE_DAC_ASSESSMENT = 'openemr-dac-assessment';

    /**
     * @var AssessmentRepository
     */
    private $assessmentService;

    public function __construct(AssessmentRepository $repository)
    {
        parent::__construct();
        $this->assessmentService = $repository;
    }

    public function supportsCode($code)
    {
        return $code === self::CODE_DAC_ASSESSMENT;
    }

    protected function loadSearchParameters()
    {
        return  [
            '_id' => new FhirSearchParameterDefinition('_id', SearchFieldType::TOKEN, [new ServiceField('uuid', ServiceField::TYPE_UUID)])
            // note what we store in the database is the Title of the questionnaire even thought its called 'name'.  The computable name is stored only in the json
            // TODO: @adunsulag look at adding a database field for the computable name and store it in the database
            ,'title' => new FhirSearchParameterDefinition('title', SearchFieldType::STRING, [new ServiceField('name', ServiceField::TYPE_STRING)])
        ];
    }

    protected function createOpenEMRSearchParameters($fhirSearchParameters, $puuidBind)
    {
        // we don't do anything with the code once we have it, so we remove it.
        if (!empty($fhirSearchParameters['questionnaire-code'])) {
            unset($fhirSearchParameters['questionnaire-code']);
        }
        return parent::createOpenEMRSearchParameters($fhirSearchParameters, $puuidBind);
    }

    public function parseOpenEMRRecord($dataRecord = array(), $encode = false)
    {
        $fhirResource = new FHIRQuestionnaire();
        $id = new FhirId();
        $id->setValue($dataRecord['uuid']);
        $fhirResource->setId($id);

        $meta = new FHIRMeta();
        $meta->setVersionId($dataRecord['version'] ?? '1');
        // TODO: @adunsulag use modified_date
        $meta->setLastUpdated(gmdate('c'));
        $fhirResource->setMeta($meta);

        $code = new FHIRCode();
        $code->setValue(self::CODE_DAC_ASSESSMENT);
        $fhirResource->addCode($code);
        $fhirResource->setName($dataRecord['uid'] ?? '');
        $fhirResource->setTitle($dataRecord['name'] ?? '');
        if ($dataRecord['isPublic']) {
            $fhirResource->setStatus('active');
        } else {
            $fhirResource->setStatus('draft');
        }

        // TODO: @adunsulag need to publish the url here
        $extension = new FHIRExtension();
        $extension->setUrl("https://www.discoverandchange.com/fhir/" . self::CODE_DAC_ASSESSMENT);
        $extension->setValueString($dataRecord['data'] ?? '');
        $fhirResource->addExtension($extension);

        return $fhirResource;
    }

    protected function searchForOpenEMRRecords($openEMRSearchParameters): ProcessingResult
    {
        return $this->assessmentService->search($openEMRSearchParameters);
    }

    public function createProvenanceResource($dataRecord, $encode = false)
    {
        // we don't return any provenance authorship for this custom resource
        // if we did return it, we would fill out the following record
        if (!($dataRecord instanceof FHIRQuestionnaire)) {
            throw new \BadMethodCallException("Data record should be correct instance class");
        }
        $fhirProvenanceService = new FhirProvenanceService();
        // provenance will just be the organization as we don't keep track of the user at the individual FHIR resource level
        // note we do track this internally in OpenEMR but FHIR R4 doesn't expose this as far as I can tell.
        $fhirProvenance = $fhirProvenanceService->createProvenanceForDomainResource($dataRecord, null);
        if ($encode) {
            return json_encode($fhirProvenance);
        } else {
            return $fhirProvenance;
        }
        $provenenance = new FHIRProvenance();
        UtilsService::createProvenanceResource($provenenance, $dataRecord, $encode);
        return null;
    }
}
