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

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRProvenance;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\FHIR\R4\FHIRResource\FHIRTask\FHIRTaskInput;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\AssignmentTaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\QuestionnairePortalTaskFHIRResourceService;
use OpenEMR\Services\FHIR\FhirProvenanceService;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\IPatientCompartmentResourceService;
use OpenEMR\Services\FHIR\IResourceReadableService;
use OpenEMR\Services\FHIR\IResourceSearchableService;
use OpenEMR\Services\FHIR\IResourceUpdateableService;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\Traits\MappedServiceCodeTrait;
use OpenEMR\Services\FHIR\Traits\MappedServiceTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\NumberSearchField;
use OpenEMR\Services\Search\ReferenceSearchField;
use OpenEMR\Services\Search\ReferenceSearchValue;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Validators\ProcessingResult;

class TaskFHIRResourceService extends FhirServiceBase implements
    IResourceReadableService,
    IResourceSearchableService,
    IResourceUpdateableService,
    IPatientCompartmentResourceService
{
    /**
     * If you'd prefer to keep out the empty methods that are doing nothing uncomment the following helper trait
     */
    use FhirServiceBaseEmptyTrait;

    use MappedServiceCodeTrait;

    /**
     * @var TaskDataStore The in memory sample data store we use for data population with our module
     */
    private $dataStore;

    public function __construct(QuestionnairePortalTaskFHIRResourceService $portalQuestionnaireResourceService, AssignmentTaskFHIRResourceService $assignmentResourceService, $fhirApiURL = null)
    {
        parent::__construct($fhirApiURL);
//        $this->addMappedService($portalQuestionnaireResourceService);
        $this->addMappedService($assignmentResourceService);
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
            '_id' => new FhirSearchParameterDefinition('_id', SearchFieldType::TOKEN, [new ServiceField('_id', ServiceField::TYPE_STRING)]),
//            'owner' => new FhirSearchParameterDefinition('owner', SearchFieldType::REFERENCE, [new ServiceField('owner_id', ServiceField::TYPE_UUID)]),
//            'status' => new FhirSearchParameterDefinition('status', SearchFieldType::TOKEN, [new ServiceField('status', ServiceField::TYPE_STRING)]),
            'patient' => $this->getPatientContextSearchField(),
            'code' => new FhirSearchParameterDefinition(
                'code',
                SearchFieldType::TOKEN,
                [new ServiceField('code', ServiceField::TYPE_STRING)]
            ),
        ];
    }

    /**
     * @return FhirSearchParameterDefinition Returns the search field definition for the patient search field
     */
    public function getPatientContextSearchField(): FhirSearchParameterDefinition
    {
        return new FhirSearchParameterDefinition('patient', SearchFieldType::REFERENCE, [new ServiceField('patient', ServiceField::TYPE_UUID)]);
    }

    /**
     * Retrieves all of the fhir task resources mapped to the underlying openemr data elements.
     * @param $fhirSearchParameters The FHIR resource search parameters
     * @param $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @return processing result
     */
    public function getAll($fhirSearchParameters, $puuidBind = null): ProcessingResult
    {
        $fhirSearchResult = new ProcessingResult();
        try {
            if (isset($puuidBind)) {
                $field = $this->getPatientContextSearchField();
                $fhirSearchParameters[$field->getName()] = $puuidBind;
            }

            if (isset($fhirSearchParameters['code'])) {
                $service = $this->getServiceForCode(
                    new TokenSearchField('code', $fhirSearchParameters['code']),
                    '' // default is empty
                );
                // if we have a service let's search on that
                if (isset($service)) {
                    $fhirSearchResult = $service->getAll($fhirSearchParameters, $puuidBind);
                } else {
                    $fhirSearchResult = $this->searchAllServices($fhirSearchParameters, $puuidBind);
                    // because we are dealing with multiple services we need to sort these results
                    $this->sortFhirSearchResult($fhirSearchResult);
                }
            } else {
                $fhirSearchResult = $this->searchAllServices($fhirSearchParameters, $puuidBind);
                // because we are dealing with multiple services we need to sort these results
                $this->sortFhirSearchResult($fhirSearchResult);
            }
        } catch (SearchFieldException $exception) {
            $systemLogger = new SystemLogger();
            $systemLogger->errorLogCaller("exception thrown", ['message' => $exception->getMessage(),
                'field' => $exception->getField(), 'trace' => $exception->getTraceAsString()]);
            // put our exception information here
            $fhirSearchResult->setValidationMessages([$exception->getField() => $exception->getMessage()]);
        }

        return $fhirSearchResult;
    }
    /**
     * @param array $dataRecord
     * @param bool $encode
     * @return TaskFHIRResource|\OpenEMR\Services\FHIR\the
     */
    public function parseOpenEMRRecord($dataRecord = array(), $encode = false)
    {
        $fhirResource = new FHIRTask();

        $meta = new FHIRMeta();
        $meta->setVersionId('1');
        $meta->setLastUpdated(gmdate('c'));
        $fhirResource->setMeta($meta);

        $id = new FhirId();
        $id->setValue($dataRecord['id']);
        $fhirResource->setId($id);

        if ($dataRecord['type'] == 'questionnaire') {
            $code = UtilsService::createCodeableConcept(
                ['questionnaire' => []],
                'http://build.fhir.org/ig/HL7/sdc/CodeSystem-temp.html',
                xlt('Questionnaire')
            );
            $fhirResource->setCode($code);
            // now we need to create the input
            $fhirTaskInput = new FHIRTaskInput();
            $fhirTaskInput->setType($code); // not sure why we specify this twice
            $fhirTaskInput->setValueReference(UtilsService::createRelativeReference('Questionnaire', $dataRecord['type_id']));
            $fhirResource->addInput($fhirTaskInput);
        } else if ($dataRecord['type'] == 'dc_assignment') {
            $code = UtilsService::createCodeableConcept(
                ['dc_assignment' => []],
                'http://build.fhir.org/ig/HL7/sdc/CodeSystem-temp.html',
                xlt('Discover and Change Assignment')
            );
            $fhirResource->setCode($code);
            // now we need to create the input
            $fhirTaskInput = new FHIRTaskInput();
            $fhirTaskInput->setType($code); // not sure why we specify this twice
            $fhirTaskInput->setValueString($dataRecord['assignment']);
            $fhirResource->addInput($fhirTaskInput);
        }

        if ($dataRecord['owner_type'] == 'patient') {
            $fhirResource->setOwner(UtilsService::createRelativeReference('Patient', $dataRecord['owner_id']));
        } else if ($dataRecord['owner_type'] == 'user') {
            $fhirResource->setOwner(UtilsService::createRelativeReference('Practitioner', $dataRecord['owner_id']));
        }

        // this is where we can update status to be FHIR statii
        $fhirStatus = $dataRecord['status'];
        switch ($dataRecord['status']) {
            case 'ready':
                $fhirStatus = 'ready';
                break;
        }
        $fhirResource->setStatus($fhirStatus);
        return $fhirResource;
    }
    /**
     * Updates a FHIR resource in the system.
     * @param $fhirResourceId The FHIR Resource ID used to lookup the existing FHIR resource/OpenEMR record
     * @param $fhirResource The FHIR resource.
     * @return The OpenEMR Service Result
     */
    public function update($fhirResourceId, FHIRDomainResource $fhirResource): ProcessingResult
    {
        if (!($fhirResource instanceof FHIRTask)) {
            throw new \InvalidArgumentException("Invalid FHIR resource type");
        }
        if (!$fhirResource->getFor()) {
            throw new \InvalidArgumentException("Missing Task.for");
        }
        $patientRef = UtilsService::parseReference($fhirResource->getFor());
        if ($patientRef['localResource'] != true || empty($patientRef['uuid'])) {
            throw new \InvalidArgumentException("Invalid Task.for");
        }
        $originalResource = $this->getOne($fhirResourceId, $patientRef['uuid']);
        if (!$originalResource->hasData()) {
            throw new \InvalidArgumentException("Invalid FHIR resource ID");
        }

        if (empty($fhirResource->getCode()) || empty($fhirResource->getCode()->getCoding())) {
            throw new \InvalidArgumentException("Invalid FHIR resource code");
        }
        foreach ($fhirResource->getCode()->getCoding() as $coding) {
            $code = $coding->getCode()->getValue();
            $service = $this->getServiceForCode(new TokenSearchField('code', $code), $code);
            if (!empty($service) && $service instanceof IResourceUpdateableService) {
                return $service->update($fhirResourceId, $fhirResource);
            }
        }
        // if we don't find a service we can update we throw an exception
        throw new \InvalidArgumentException("Invalid FHIR resource code");
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
        if (!($dataRecord instanceof FHIRTask)) {
            throw new \InvalidArgumentException("Invalid FHIR resource type");
        }
        $provenanceService = new FhirProvenanceService();
        $who = null;
        if (!empty($dataRecord->getRequester()) && $dataRecord->getRequester()->getType() == 'Practitioner') {
            $who = $dataRecord->getRequester();
        }
        return $provenanceService->createProvenanceForDomainResource($dataRecord, $who);
    }

    private function sortFhirSearchResult(ProcessingResult $fhirSearchResult)
    {
        $data = $fhirSearchResult->getData();
        // TODO: @adunsulag sort the data in memory since we are dealing with multiple services
        return $fhirSearchResult;
    }
}
