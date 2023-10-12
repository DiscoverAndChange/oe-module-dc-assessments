<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices;

use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRProvenance;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Logging\LoggerAwareTrait;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentResultBlobValidator;
use OpenEMR\Services\FHIR\FhirProvenanceService;
use OpenEMR\Services\FHIR\FhirServiceBase;
use OpenEMR\Services\FHIR\Traits\FhirServiceBaseEmptyTrait;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\FhirSearchParameterDefinition;
use OpenEMR\Services\Search\SearchFieldType;
use OpenEMR\Services\Search\ServiceField;
use OpenEMR\Validators\ProcessingResult;
use Ramsey\Uuid\Uuid;

class AssessmentResponseBlobFHIRResourceService extends FhirServiceBase
{
    use FhirServiceBaseEmptyTrait;
    use LoggerAwareTrait;

    const CODE_DAC_ASSESSMENT = 'openemr-dac-assessment-response';

    public function __construct(
        private AssessmentResultRepository $repository,
        private AssignmentRepository $assignmentRepository,
        private AssignmentCompleter $completer,
        private PatientService $patientService
    ) {
        parent::__construct();
    }

    public function supportsCode($code)
    {
        return $code === self::CODE_DAC_ASSESSMENT;
    }

    protected function loadSearchParameters()
    {
        return  [
            '_id' => new FhirSearchParameterDefinition('_id', SearchFieldType::TOKEN, [new ServiceField('id', ServiceField::TYPE_STRING)])
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
        $id->setValue($dataRecord['_id']);
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
        $fhirResource->setStatus('active');

        // TODO: @adunsulag need to publish the url here
        $extension = new FHIRExtension();
        $extension->setUrl("https://www.discoverandchange.com/fhir/" . self::CODE_DAC_ASSESSMENT);
        $extension->setValueString(json_encode($dataRecord));
        $fhirResource->addExtension($extension);

        return $fhirResource;
    }

    public function insert(FHIRDomainResource $fhirResource): ProcessingResult
    {
        $openEmrRecord = $this->parseFhirResource($fhirResource);
        $result = $this->insertOpenEmrRecord($openEmrRecord);
        // we just return the id of the created resource here.... now we have to be able to retrieve it...
        return $result;
    }

    public function parseFhirResource(FHIRDomainResource $fhirResource)
    {
        if (!($fhirResource instanceof FHIRQuestionnaireResponse)) {
            throw new \BadMethodCallException("FHIR resource should be correct instance class");
        }
        $extensions = UtilsService::getExtensionsByUrl("https://www.discoverandchange.com/fhir/" . self::CODE_DAC_ASSESSMENT, $fhirResource);
        if (!empty($extensions)) { // we only care about the first one.
            $valueString = $extensions[0]->getValueString();
            $dataRecord = json_decode($valueString, true);
            if ($dataRecord) {
                $author = UtilsService::parseReference($fhirResource->getAuthor());
                $dataRecord['clientId'] = $author['uuid'];
                return $dataRecord;
            }
        }
        return null;
    }

    protected function insertOpenEMRRecord($openEmrRecord)
    {
        $validator = new AssessmentResultBlobValidator();
        $transactionCommitted = false;
        try {
            $assignmentRepo = $this->assignmentRepository;
            QueryUtils::startTransaction();
            $validation = $validator->validate($openEmrRecord, AssessmentResultBlobValidator::DATABASE_INSERT_CONTEXT);
            if (!$validation->isValid()) {
                return $validation;
            }

            $client = $this->validateCreateAccessAndReturnClient($openEmrRecord['clientId'], $_SESSION['authUserId'] ?? null);
            $item = $assignmentRepo->getAssignmentItem($openEmrRecord['data']['_assignmentItemId'], $client['uuid']);
            if (empty($item)) {
                throw new \InvalidArgumentException("Assignment item not found", ErrorCode::INVALID_REQUEST);
            } else if (!($item instanceof AssignedAssessment)) {
                throw new \InvalidArgumentException("Assignment item is not an assessment", ErrorCode::INVALID_REQUEST);
            }
            $resultRepo = new AssessmentResultRepository();
            $resultId = Uuid::uuid4()->toString();
            $item->setResultId($resultId);
            $savedResult = $resultRepo->createResult($resultId, $openEmrRecord, $client['pid'], $item->getAssessmentId());

            if (empty($item)) {
                throw new \InvalidArgumentException("Assignment item not found", ErrorCode::INVALID_REQUEST);
            }
            if (!($item instanceof AssignedAssessment)) {
                throw new \InvalidArgumentException("Assignment item was not a valid assessment assignment.  This code should not have been reached", ErrorCode::INVALID_REQUEST);
            }
            $result = new ProcessingResult();
            $result->addData($resultId);
            $this->completer->markAssignmentComplete($item, $client);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
        } catch (AccessDeniedException $exception) {
            $this->getLogger()->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $result = new ProcessingResult();
            $result->addInternalError(xlt("You do not have permission to create this result"));
        } catch (\Exception $exception) {
            $this->getLogger()->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $result = new ProcessingResult();
            $result->addInternalError(xlt("A system error occurred in processing your request"));
        } finally {
            if (!$transactionCommitted) {
                QueryUtils::rollbackTransaction();
            }
        }
        return $result;
    }

    protected function searchForOpenEMRRecords($openEMRSearchParameters): ProcessingResult
    {
        return $this->repository->search($openEMRSearchParameters);
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


    private function validateCreateAccessAndReturnClient(string $patientUuidString, ?int $userId)
    {

        if (empty($patientUuidString)) {
            throw new AccessDeniedException("encounters", "notes", "You do not have permission to create this result");
        } else if (!empty($userId && !AclMain::aclCheckCore("encounters", "Notes"))) {
            throw new AccessDeniedException("encounters", "notes", "You do not have permission to create this result");
        } else {
            // need to grab the patient pid from the uuid
            $result = $this->patientService->getOne($patientUuidString);
            if (!$result->hasData()) {
                throw new \InvalidArgumentException("Patient uuid in request does not exist", ErrorCode::SYSTEM_ERROR);
            } else {
                $client = $result->getData()[0];
            }
        }
        return $client;
    }
}
