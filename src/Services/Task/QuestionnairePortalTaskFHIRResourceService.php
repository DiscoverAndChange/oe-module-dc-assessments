<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\FHIR\R4\FHIRResource\FHIRTask\FHIRTaskInput;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskOnsitePortalActivityAccessService;
use OpenEMR\Services\DocumentTemplates\DocumentTemplateService;
use OpenEMR\Services\FHIR\FhirCodeSystemConstants;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\IPatientCompartmentResourceService;
use OpenEMR\Services\FHIR\IResourceUpdateableService;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\SearchFieldException;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Validators\ProcessingResult;
use Symfony\Component\Process\Process;

class QuestionnairePortalTaskFHIRResourceService extends FhirServiceBase implements IResourceUpdateableService, IPatientCompartmentResourceService
{
    use FhirServiceBaseEmptyTrait;

    const FHIR_TASK_CODE = 'complete-questionnaire';


    public function supportsCode($code)
    {
        // @see sdc-t1 https://build.fhir.org/ig/HL7/sdc/StructureDefinition-sdc-task.html
        return $code == 'complete-questionnaire';
    }

    // in parsing this we need to do the following: https://build.fhir.org/ig/HL7/sdc/StructureDefinition-sdc-task.html
    public function parseOpenEMRRecord($dataRecord = array(), $encode = false): FHIRTask
    {
        $fhirResource = new FHIRTask();

        $meta = new FHIRMeta();
        $meta->setVersionId('1');
        // TODO: @adunsulag look at using 'modified_date' here
        $meta->setLastUpdated(gmdate('c'));
        $fhirResource->setMeta($meta);

        $id = new FhirId();
        $id->setValue($dataRecord['id']); // TODO: @adunsulag this should be a uuid for DC stuff.
        $fhirResource->setId($id);
        $fhirResource->setDescription($dataRecord['type_display_text']);
        $code = UtilsService::createCodeableConcept(
            [self::FHIR_TASK_CODE => []],
            FhirCodeSystemConstants::HL7_SDC_TASK_TEMP, // TODO: @adunsulag do we want to expose a custom CodeSystem?
            xlt("Complete Questionnaire")
        );
        $fhirResource->setCode($code);

        // now we need to create the input
        $fhirTaskInput = new FHIRTaskInput();
        $uri = UtilsService::createCanonicalUrlForResource('Questionnaire', $dataRecord['type_uuid']);
        $fhirTaskInput->setType(UtilsService::createCodeableConcept(['questionnaire' => []], FhirCodeSystemConstants::HL7_SDC_TASK_TEMP, xl("Questionnaire")));
        $fhirTaskInput->setValueCanonical($uri); // sdc spec requires this to be a URI
        $fhirResource->addInput($fhirTaskInput);
        // we are only working in the portal for assignments here so this is something for the patient to do.
        $fhirResource->setOwner(UtilsService::createRelativeReference('Patient', $dataRecord['owner_uuid']));

        // owner and For are the same person since we are working in the portal.  The patient is the beneficiary of this task.
        $fhirResource->setFor(UtilsService::createRelativeReference('Patient', $dataRecord['patient_uuid']));
        $fhirResource->setStatus($dataRecord['status']);
        return $fhirResource;
    }

    public function update($fhirResourceId, FHIRDomainResource $fhirResource): ProcessingResult
    {
        // the fhirResourceId is the task id, which is also the template id for now.

        $qrService = new QuestionnaireResponseService();
        // for now we only support updating the status property, we grab the uuid for the template from the input
        // and then we update the status of the task.

        // if the status is completed, we need to make sure we get the notification out to the admin team to review
        // the record so it can be charted.  This should complete the workflow.
        if (!($fhirResource instanceof FHIRTask)) {
            throw new \InvalidArgumentException("Invalid FHIR resource type passed to update");
        }
        $status = $fhirResource->getStatus()->getValue();
        if ($status == 'completed') {
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


    protected function searchForOpenEMRRecords($openEMRSearchParameters): ProcessingResult
    {
        $processingResult = new ProcessingResult();

        $docTemplateService = new DocumentTemplateService();
        // TODO: @adunsulag need to handle both the owner_id search field here as well as the patient search field piece here
        // one approach is to force a requirement that the owner_id must be passed and we only respond to Patient/* reference requests.

        if (empty($openEMRSearchParameters['patient'])) {
            // if we aren't doing an id and the patient field is empty we throw an exception
            throw new SearchFieldException('patient', "The patient field is required");
        }
        $filterByTemplateId = null;
        $templates = [];
        $patientPids = [];
        $patientField = $openEMRSearchParameters['patient'];

        $patientIds = $patientField->getValues();
        foreach ($patientIds as $id) {
            $patientService = new PatientService();
            $pid = $patientService->getPidByUuid($id->getId());
            $patientPids[] = $pid;
        }
        // super inefficient, but the only way to grab a single task is by hitting the patients right now
        if (!empty($openEMRSearchParameters['_id'])) {
            $filterByTemplateId = $openEMRSearchParameters['_id']->getValues()[0]->getCode();
//            $template = $docTemplateService->fetchTemplate($filterByTemplateId);
//            if (!empty($template)) {
//                if (in_array($template['pid'], $patientPids)) {
//                    $templates[] = $template;
//                }
//            }
        }
        if (!empty($patientPids)) {
            $templates = [];
            // in the patient context there is always only going to be one, but if we open this up to providers there will
            // be multiples.
            foreach ($patientPids as $pid) {
                // TODO: @adunsulag the current approach does not as far as I can tell give us any unique identifier for the
                // task when its a repeating doc template.  We need to figure out how to handle this.
                $templates_call = $docTemplateService->getPortalAssignedTemplates($pid, 'questionnaire', true);
                $questionnaires = $templates_call['questionnaire'] ?? []; // make sure we only deal with questionnaires.
                foreach ($questionnaires as $questionnaire) {
                    if (empty($filterByTemplateId) || $questionnaire['id'] == $filterByTemplateId) {
                        $templates[] = $questionnaire;
                    }
                }
            }
        }
        if (empty($templates)) {
            return $processingResult; // nothing to do here as there are no assignments to be returned.
        }

        return $this->getTaskDataForTemplates($docTemplateService, $processingResult, $templates);
    }

    private function getTaskDataForTemplates($docTemplateService, ProcessingResult $processingResult, $templates): ProcessingResult
    {


        // array walk the templates and grab the template ids from each one to use in a sql query
        $pids = [];
        $ids = [];
        foreach ($templates as $template) {
            $pids[] = intval($template['pid']);
            $ids[] = intval($template['id']);
        }
        $pidsRepeat = str_repeat('?,', count($pids) - 1) . '?';
        $filePathRepeat = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM `onsite_documents` WHERE `pid` IN (" . $pidsRepeat . ") AND `file_path` IN ("
            . $filePathRepeat . ") ORDER BY `create_date` DESC";
        $docs = QueryUtils::fetchRecords($sql, array_merge($pids, $ids));
        $docMap = [];
        // TODO: @adunsulag I'm not sure how repeat of the same document are handled here...
        foreach ($docs as $doc) {
            if (empty($docMap[$doc['id']])) {
                $docMap[$doc['pid']] = [];
            }
            $docMap[$doc['pid']][$doc['file_path']] = $doc;
        }
        $repeat = str_repeat('?,', count($pids) - 1) . '?';
        $patients = QueryUtils::fetchRecords("SELECT uuid,pid FROM `" . PatientService::TABLE_NAME . "` WHERE `pid` IN (" . $repeat . ") ", $pids);
        $patientMap = [];
        foreach ($patients as $patient) {
            $patientMap[$patient['pid']] = UuidRegistry::uuidToString($patient['uuid']);
        }

        foreach ($templates as $template) {
            // grab the questionnaire content and let's get the id using a regex from template {Questionnaire:id}
            $questionnaire = $template['template_content'];
            $id = null;
            if (preg_match('/{Questionnaire:\s*(\d+)}/', $questionnaire, $matches)) {
                $id = $matches[1];
            }
            if (!empty($id)) {
                $templatePid = $template['pid'];
                $shouldShow = true;
                $resourceRecord = [
                    'status' => 'ready'
                ];
                if (isset($docMap[$templatePid]) && isset($docMap[$templatePid][$template['id']])) {
                    $document = $docMap[$templatePid][$template['id']];

                    // TODO: @adunsulag should 'Reviewing' be set to 'in-progress' even though its submitted?
                    if (
                        $document['denial_reason'] == DocumentTemplateService::DENIAL_STATUS_IN_REVIEW
                        || $document['denial_reason'] == DocumentTemplateService::DENIAL_STATUS_LOCKED
                    ) {
                        $resourceRecord['status'] = 'completed';
                    } else if ($document['denial_reason'] == DocumentTemplateService::DENIAL_STATUS_EDITING) {
                        $resourceRecord['status'] = 'in-progress';
                    } else {
                        // need to figure out our timed templates and whether we show them or not
                        $shouldShow = $docTemplateService->showTemplateFromEvent($template) !== false;
                    }
                }
                if (!$shouldShow) {
                    continue; // skip over templates as its a repeating event that we aren't ready to turn into a task
                }
                $questionnaireService = new QuestionnaireService();
                $record = $questionnaireService->fetchQuestionnaireById($id);

                $resourceRecord['id'] = $template['id']; // this is the task id, for now we will use the template id
                $resourceRecord['type'] = 'questionnaire'; // same as category but this is a specific FHIR code
                $resourceRecord['type_table'] = QuestionnaireService::TABLE_NAME;
                $resourceRecord['type_uuid'] = $record['uuid'];
                $resourceRecord['type_display_text'] = $record['name'];
                $resourceRecord['owner_type'] = 'patient';
                $resourceRecord['owner_uuid'] = $patientMap[$templatePid] ?? null;
                $resourceRecord['patient_uuid'] = $patientMap[$templatePid] ?? null;
                $resourceRecord['requestor_type'] = 'user';
                // TODO: we don't expose the provider here yet...
                $resourceRecord['requestor_uuid'] = null;
                $resourceRecord['created_date'] = $template['profile_date'];
                $resourceRecord['modified_date'] = $template['modified_date'];
                $processingResult->addData($resourceRecord);
            }
        }
        return $processingResult;
    }
}
