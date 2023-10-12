<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task;

use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\FHIR\R4\FHIRResource\FHIRTask\FHIRTaskInput;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskOnsitePortalActivityAccessService;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\IResourceUpdateableService;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\SearchModifier;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Services\Search\TokenSearchValue;
use OpenEMR\Validators\ProcessingResult;

class AssignmentTaskFHIRResourceService extends FhirServiceBase implements IResourceUpdateableService
{
    use FhirServiceBaseEmptyTrait;

    const DAC_ASSIGNMENT = "complete-dac-assignment";

    const DC_ASSIGNMENT_CODE_ASSESSMENT = "complete-dc-assessment";
    const DC_ASSIGNMENT_CODE_LIBRARY = "complete-dc-libraryasset";
    const DC_ASSIGNMENT_CODE_QUESTIONNAIRE = "complete-dc-questionnaire";

    const DC_ASSIGNMENT_CODE_GROUP = "complete-dc-group";

    const COLUMN_MAPPINGS = [
        self::DC_ASSIGNMENT_CODE_ASSESSMENT => [
            'column' => 'assessmentblob_id'
            ,'type' => 'Assessment'
            ,'description' => 'Complete Assessment'
        ]
        ,self::DC_ASSIGNMENT_CODE_LIBRARY => [
            'column' => 'assetresultblob_id'
            ,'type' => 'LibraryAsset'
            ,'description' => 'Complete Library Assignment'
        ],
        self::DC_ASSIGNMENT_CODE_QUESTIONNAIRE => [
            'column' => 'questionnaire_uuid'
            ,'type' => 'Questionnaire'
            ,'description' => "Complete Questionnaire"
        ],
        self::DC_ASSIGNMENT_CODE_GROUP => [
            'column' => 'assessmentgroup_id'
            ,'type' => 'AssessmentGroup'
            ,'description' => "Complete Assessment Group"
        ]
    ];

    public function __construct(private AssignmentRepository $repository, $fhirApiURL = null)
    {
        parent::__construct($fhirApiURL);
    }


    public function supportsCode($code)
    {
        return $code == self::DAC_ASSIGNMENT;
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
            // tasks can be mapped to the container assignment or to the individual assignment items.
            '_id' => new FhirSearchParameterDefinition('_id', SearchFieldType::TOKEN, [new ServiceField('assignment_uuid', ServiceField::TYPE_STRING), new ServiceField('assignmentitem_uuid', ServiceField::TYPE_UUID)]),
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
        return new FhirSearchParameterDefinition('patient', SearchFieldType::REFERENCE, [new ServiceField('client_uuid', ServiceField::TYPE_UUID)]);
    }

    public function parseOpenEMRRecord($dataRecord = array(), $encode = false): FHIRTask
    {
        $fhirResource = new FHIRTask();

        $meta = new FHIRMeta();
        $meta->setVersionId('1');
        $meta->setLastUpdated(gmdate('c'));
        $fhirResource->setMeta($meta);

        $id = new FhirId();
        $id->setValue($dataRecord['id']); // TODO: @adunsulag this should be a uuid for DC stuff.
        $fhirResource->setId($id);
        $code = UtilsService::createCodeableConcept(
            [self::DAC_ASSIGNMENT => []],
            '', // TODO: @adunsulag do we want to expose a custom CodeSystem?
            xl("Complete Assignment")
        );
        $fhirResource->setCode($code);

        // now we need to create the input
        $fhirTaskInput = new FHIRTaskInput();
        $fhirTaskInput->setType(UtilsService::createCodeableConcept([$dataRecord['type'] => []], '', xl("Complete " . $dataRecord['type']))); // not sure why we specify this twice
        $fhirTaskInput->setValueString(json_encode($dataRecord));
        $fhirResource->addInput($fhirTaskInput);
        // we are only working in the portal for assignments here so this is something for the patient to do.
        $fhirResource->setOwner(UtilsService::createRelativeReference('Patient', $dataRecord['clientId']));

        if (!empty($dataRecord['assignment_uuid'])) {
            $fhirResource->addPartOf(UtilsService::createRelativeReference('Task', $dataRecord['assignment_uuid']));
        }

        if (!empty($dataRecord['dateAssigned'])) {
            $fhirResource->setAuthoredOn($dataRecord['dateAssigned']);
        }

        if (empty($dataRecord['dateCompleted'])) {
            $fhirStatus = 'ready';
        } else {
            $fhirStatus = 'completed';
        }

        $fhirResource->setStatus($fhirStatus);
        return $fhirResource;
    }

    protected function searchForOpenEMRRecords($openEMRSearchParameters): ProcessingResult
    {
        if (isset($openEMRSearchParameters['code']) && $openEMRSearchParameters['code'] instanceof TokenSearchField) {
            $codes = $openEMRSearchParameters['code']->getValues();

            // if we only have assessment but not group
            foreach ($codes as $code) {
                $codeValue = $code->getCode();
                if (isset(self::COLUMN_MAPPINGS[$codeValue])) {
                    $column = self::COLUMN_MAPPINGS[$codeValue]['column'];
                    $openEMRSearchParameters[$column] = new TokenSearchField($column, [new TokenSearchValue(false)]);
                    $openEMRSearchParameters[$column]->setModifier(SearchModifier::MISSING);
                }
            }
            unset($openEMRSearchParameters['code']);
        }

        // we definitely want the intersection and not a union search as we want search parameters to be more restrictive
        $isAndCondition = true;
        $results = $this->repository->search($openEMRSearchParameters, $isAndCondition);
        $processingResult = new ProcessingResult();
        // since we retrieve results of the assignment and individual assignment items which map to tasks
        // using the same _id search parameter, we need to filter
        // for the specific uuid and exclude either the group or the child items based on the values of the _id
        $matchedUUids = [];
        $matchSearchId = false;
        if (!empty($results)) {
            if (isset($openEMRSearchParameters['_id'])) {
                $matchSearchId = true;
                $values = $openEMRSearchParameters['_id']->getValues();
                $matchedUUids = array_map(function (TokenSearchValue $value) {
                    return $value->getCode();
                }, $values);
            }
            foreach ($results as $result) {
                $resultData = $result->jsonSerialize();
                if (!$matchSearchId || in_array($result->getId(), $matchedUUids)) {
                    $processingResult->addData($resultData); // we get our data array to return
                }
                // add the individual sub tasks as part of the search
                if ($result->isGroupType()) { // group items
                    foreach ($resultData['items'] as $item) {
                        $item['assignment_uuid'] = $result->getId();
                        if (!$matchSearchId || in_array($item['id'], $matchedUUids)) {
                            $processingResult->addData($item);
                        }
                    }
                }
            }
        }

        return $processingResult;
    }

    public function update($fhirResourceId, FHIRDomainResource $fhirResource): ProcessingResult
    {
        // for now we only support updating the status property, we grab the uuid for the template from the input
        // and then we update the status of the task.

        // if the status is completed, we need to make sure we get the notification out to the admin team to review
        // the record so it can be charted.  This should complete the workflow.
        if (!($fhirResource instanceof FHIRTask)) {
            throw new \InvalidArgumentException("Invalid FHIR resource type passed to update");
        }
        // grab the assignment
        $assignment = $this->repository->getAssignmentByUuid($fhirResourceId);
        $assignmentForItem = $this->repository->getAssignmentForAssignmentItemUuid($fhirResourceId);
        if ($assignment === null || $assignmentForItem == null) {
            throw new \InvalidArgumentException("Invalid FHIR resource id passed to update");
        }
        if (!empty($assignmentForItem)) {
            return $this->updateAssignmentItem($assignmentForItem, $fhirResourceId, $fhirResource);
        } else {
            return $this->updateAssignment($assignment, $fhirResourceId, $fhirResource);
        }
    }

    private function updateAssignment(Assignment $assignment, string $fhirResourceId, FHIRTask $fhirResource)
    {
        $qrService = new QuestionnaireResponseService();

        $status = $fhirResource->getStatus()->getValue();
        if ($status == 'completed') {
            if ($assignment->getIsComplete()) {
                // treating this as idempotent as the task is already completed
                $processingResult = new  ProcessingResult();
                $processingResult->addData($fhirResourceId);
                return $processingResult;
            }

            $qrUuid = null;
            // grab the output
            // TODO: @adunsulag validate that the outputs exist
            $output = $fhirResource->getOutput()[0]->getValueReference();
            $parsedReference = UtilsService::parseReference($output);
            if ($parsedReference['localResource']) {
                $qrUuid = $parsedReference['uuid'];
                $response = $qrService->fetchQuestionnaireResponseByResponseId($qrUuid);
                if (empty($response)) {
                    throw new \InvalidArgumentException("FHIRTask.output[0].valueReference is invalid");
                }
                // TODO: @adunsulag we need to check the questionnaire response pid against the Task.for property and make sure they match
                // if they are different then someone is trying to assign a questionnaire response to a patient that doesn't belong to them.
                $questionnaireJSON = $response['questionnaire'];
                $questionnaire = json_decode($questionnaireJSON, true, 512, JSON_THROW_ON_ERROR);
                $resourceService = new TaskOnsitePortalActivityAccessService();
                $patientService = new PatientService();
                $puuid = UuidRegistry::uuidToString($patientService->getUuid($response['patient_id']));
                $portalAuditId = $resourceService->createOnSitePortalActivity($puuid, 'questionnaire', $questionnaire['title'], $qrUuid);

                // now create the pdf document
                // we stuff it in the In Review category
                $category = QueryUtils::fetchSingleValue("SELECT id FROM categories WHERE name = ?", 'id', ['Reviewed']) ?: 3;
                $questionnaireResponsePDFService = new QuestionnaireResponseOnSiteDocumentService($qrService);
                $createdDoc = $questionnaireResponsePDFService->createDocument($fhirResourceId, $category, $response, $questionnaire);

                // now we need to update the TemplateProfile task to point to the document

                $processingResult = new  ProcessingResult();
                $processingResult->addData($fhirResourceId);
                return $processingResult;
            } else {
                throw new \InvalidArgumentException("Cannot process external QuestionnaireResponse");
                // TODO: @adunsulag we shouldn't be getting non-local resources... but handle this case
            }
        } else {
            // TODO: @adunsulag if we ever implement other kinds of statii update we'd do that here.
            throw new \InvalidArgumentException("Status update not supported on this resource server");
        }
    }
}
