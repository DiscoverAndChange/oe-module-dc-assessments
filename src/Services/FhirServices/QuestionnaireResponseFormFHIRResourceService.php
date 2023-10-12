<?php

/**
 * FHIR Resource Service class example for implementing the methods that are typically used with FHIR resources via the
 * api.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2022 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRProvenance;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Services\EncounterService;
use OpenEMR\Services\FHIR\FhirProvenanceService;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\IResourceCreatableService;
use OpenEMR\Services\FHIR\IResourceReadableService;
use OpenEMR\Services\FHIR\IResourceSearchableService;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\Traits\PatientSearchTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Validators\ProcessingResult;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class QuestionnaireResponseFormFHIRResourceService extends FhirServiceBase implements IResourceReadableService, IResourceSearchableService, IResourceCreatableService
{
    /**
     * If you'd prefer to keep out the empty methods that are doing nothing uncomment the following helper trait
     */
    use FhirServiceBaseEmptyTrait;
    use PatientSearchTrait;

    /**
     * @var QuestionnaireResponseService
     */
    private $service;

    public function __construct($fhirApiURL = null)
    {
        parent::__construct($fhirApiURL);
        $this->service = new QuestionnaireResponseService();
    }

    public function parseFhirResource(FHIRDomainResource $fhirResource)
    {
        if (!($fhirResource instanceof FHIRQuestionnaireResponse)) {
            throw new \InvalidArgumentException("resource must be of type " . FHIRQuestionnaireResponse::class);
        }

        $parsedResource = [];
        if (!empty($fhirResource->getId())) {
            $parsedResource['response_id'] = $fhirResource->getId()->getValue();
            $parsedResource['uuid'] = UuidRegistry::uuidToBytes($parsedResource['response_id']);
        }
        // required value so should be here
        if (!empty($fhirResource->getQuestionnaire())) {
            $parsedUrl = UtilsService::parseCanonicalUrl($fhirResource->getQuestionnaire());
            if ($parsedUrl['localResource']) {
                $parsedResource['questionnaire_id'] = $parsedUrl['uuid'];
            } else {
                throw new \InvalidArgumentException("Questionnaire does not exist on local server. Cannot save QuestionnaireResponse.");
            }
        }
        // our subjects at this point should really only be the patient...
        if (!empty($fhirResource->getSubject())) {
            $parsedReference = UtilsService::parseReference($fhirResource->getSubject());
            if ($parsedReference['localResource']) {
                if (!empty($parsedReference['type']) == 'Patient') {
                    $parsedResource['puuid'] = $parsedReference['uuid'];
                } else {
                    // handle something different here... if we are working with organization or anything
                }
            } else {
                throw new \InvalidArgumentException("Subject does not exist on local server. Cannot save QuestionnaireResponse.");
            }
        }
        if (!empty($fhirResource->getEncounter())) {
            $parsedReference = UtilsService::parseReference($fhirResource->getEncounter());
            if ($parsedReference['localResource']) {
                $parsedReference['encounter_uuid'] = $parsedResource['uuid'];
            } else {
                throw new \InvalidArgumentException("Subject does not exist on local server. Cannot save QuestionnaireResponse.");
            }
        }
        if (!empty($fhirResource->getSource())) {
            $parsedReference = UtilsService::parseReference($fhirResource->getSource());
            if ($parsedReference['localResource']) {
                if (!empty($parsedReference['type']) == 'Practitioner') {
                    $parsedResource['creator_user_uuid'] = $parsedReference['uuid'];
                } else {
                    // handle something different here... if we are working with organization or anything
                }
            } else {
                throw new \InvalidArgumentException("Subject does not exist on local server. Cannot save QuestionnaireResponse.");
            }
        }
        $status = $fhirResource->getStatus();
        if ($status == 'in-progress') {
            $status = 'incomplete';
        }
        $parsedResource['status'] = $status;

        $parsedResource['questionnaire_response'] = json_encode($fhirResource);
        if (!empty($fhirResource->getMeta())) {
            if (!empty($fhirResource->getMeta()->getId())) {
                $parsedResource['version'] = $fhirResource->getMeta()->getVersionId()->getValue() ?? 1;
            }
        }
        return $parsedResource;
    }

    /**
     * @param array $dataRecord
     * @param bool $encode
     * @return TaskFHIRResource|\OpenEMR\Services\FHIR\the
     */
    public function parseOpenEMRRecord($dataRecord = array(), $encode = false)
    {
        $innerData = [];
        try {
            // parse the json data in dataRecord questionnaire
            $innerData = json_decode($dataRecord['questionnaire_response'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            // log the error and move on
            $innerData = []; // nothing we can do here, but skip the questionnaire data as its invalid
            (new SystemLogger())->errorLogCaller(
                "Unable to parse questionnaire json",
                ['uuid' => $dataRecord['uuid'] ?? '', 'message' => $exception->getMessage()
                ,
                'trace' => $exception->getTraceAsString()]
            );
        }
        $fhirResource = new FHIRQuestionnaireResponse($innerData);

        $meta = new FHIRMeta();
        $meta->setVersionId($dataRecord['version'] ?? '1');
        // TODO: @adunsulag use modified_date
        $meta->setLastUpdated(gmdate('c'));
        $fhirResource->setMeta($meta);

        $id = new FhirId();
        $id->setValue($dataRecord['questionnaire_response_uuid']);
        $fhirResource->setId($id);

        // we trust the db records rather than the JSON as our master record if we have it.
        if (!empty($dataRecord['questionnaire_id'])) {
            $fhirResource->setQuestionnaire(UtilsService::createCanonicalUrlForResource('Questionnaire', $dataRecord['questionnaire_id']));
        }

        if (!empty($dataRecord['encounter_uuid'])) {
            $fhirResource->setEncounter(UtilsService::createRelativeReference('Encounter', $dataRecord['encounter_uuid']));
        } else {
            $fhirResource->setEncounter(null);
        }
        if (!empty($dataRecord['puuid'])) {
            $fhirResource->setSubject(UtilsService::createRelativeReference('Patient', $dataRecord['puuid']));
        } else {
            $fhirResource->setSubject(null);
        }
        if (empty($dataRecord['creator_user_id'])) {
            $fhirResource->setSource(UtilsService::createRelativeReference('Patient', $dataRecord['puuid']));
        } else if (!empty($dataRecord['creator_user_uuid'])) {
            $fhirResource->setSource(UtilsService::createRelativeReference('Practitioner', $dataRecord['creator_user_uuid']));
        } else {
            // TODO: if we ever want to support medical devices or organizations we would put that here.
            $fhirResource->setSource(null);
        }
        if (!empty($dataRecord['create_time'])) {
            $fhirResource->setAuthored(\DateTime::createFromFormat("Y-m-d H:i:s", $dataRecord['create_time'])->format(\DateTime::ATOM));
        }
        if (!empty($dataRecord['status'])) {
            // map the statii
            switch ($dataRecord['status']) {
                case 'completed':
                case 'amended':
                case 'entered-in-error':
                case 'stopped':
                    $fhirResource->setStatus($dataRecord['status']);
                    break;
                case 'incomplete':
                case 'active':
                default:
                    $fhirResource->setStatus('in-progress');
                    break;
            }
        }

        return $fhirResource;
    }

    /**
     * This method returns the FHIR search definition objects that are used to map FHIR search fields to OpenEMR fields.
     * Since the mapping can be one FHIR search object to many OpenEMR fields, we use the search definition objects.
     * Search fields can be combined as Composite fields and represent a host of search options.
     * @see https://www.hl7.org/fhir/search.html to see the types of search operations, and search types that are available
     * for use.
     * @return array
     */
    protected function loadSearchParameters()
    {
        return  [
            '_id' => new FhirSearchParameterDefinition(
                '_id',
                SearchFieldType::TOKEN,
                [new ServiceField('questionnaire_response_uuid', ServiceField::TYPE_UUID)]
            )
            ,'questionnaire' => new FhirSearchParameterDefinition(
                'questionnaire',
                SearchFieldType::REFERENCE,
                [new ServiceField('questionnaire_uuid', ServiceField::TYPE_UUID)]
            )
            ,'patient' => $this->getPatientContextSearchField()
            ,'authored' => new FhirSearchParameterDefinition(
                'authored',
                SearchFieldType::DATETIME,
                [new ServiceField('create_time', ServiceField::TYPE_STRING)]
            )
        ];
    }

    protected function searchForOpenEMRRecords($openEMRSearchParameters): ProcessingResult
    {
        return $this->service->search($openEMRSearchParameters);
    }

    /**
     * Healthcare resources often need to provide an AUDIT trail of who last touched a resource and when was it modified.
     * The ownership and AUDIT trail in FHIR is done via the Provenance record.
     * @param FHIRDomainResource $dataRecord The record we are generating a provenance from
     * @param bool $encode Whether to serialize the record or not
     * @return FHIRProvenance
     */
    public function createProvenanceResource($dataRecord, $encode = false)
    {
        // we don't return any provenance authorship for this custom resource
        // if we did return it, we would fill out the following record
//        $provenance = new FHIRProvenance();
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

    public function insertOpenEMRRecord($openEmrRecord)
    {
        /**
         * $response,
        $pid,
        $encounter = null,
        $qr_id = null,
        $qr_record_id = null,
        $q = null,
        $q_id = null,
        $form_response = null,
        $add_report = false,
        $scores = []
         */
        $patientId = null;
        $patientService = new PatientService();
        $patientRecords = ProcessingResult::extractDataArray($patientService->getOne($openEmrRecord['puuid']));
        if (empty($patientRecords)) {
            throw new \InvalidArgumentException("Patient does not exist");
        }
        $patientId = $patientRecords[0]['pid'];
        $encounterId = null;
        if (!empty($openEmrRecord['encounter_uuid'])) {
            $encounterService = new EncounterService();
            $encounterRecords = ProcessingResult::extractDataArray($encounterService->getEncounter($openEmrRecord['encounter_uuid']));
            if (empty($encounterRecords)) {
                throw new \InvalidArgumentException("Encounter does not exist");
            }
            $encounterId = $encounterRecords[0]['eid'];
        }
        // note https://build.fhir.org/http.html#create specification states that an id SHALL be ignored for our create
        // operation so we ignore any record ids here.
        $qr_id = null;
        $qr_record_id = null; // what is this even used for?
        $q = null;
        $questionnaireService = new QuestionnaireService();
        $tokenSearchValue = new TokenSearchField('uuid', [$openEmrRecord['questionnaire_id']], true);
        $questionnaireRecords = ProcessingResult::extractDataArray($questionnaireService->search(['uuid' => $tokenSearchValue]));
        if (empty($questionnaireRecords)) {
            throw new \InvalidArgumentException("Questionnaire does not exist");
        }
        $questionnaire = $questionnaireRecords[0];

        $form_response = null; // not sure why we are saving this off...
        $add_report = true; // I think we want to always generate a narrative here.

        $saved = $this->service->saveQuestionnaireResponse(
            $openEmrRecord['questionnaire_response'],
            $patientId,
            $encounterId,
            $qr_id,
            $qr_record_id,
            $questionnaire['questionnaire'],
            $openEmrRecord['questionnaire_id'],
            $form_response,
            $add_report
        );
        // return the newly created resource id
        $processingResult = new ProcessingResult();
        $processingResult->addData($saved['response_id']);
        return $processingResult;
    }
}
