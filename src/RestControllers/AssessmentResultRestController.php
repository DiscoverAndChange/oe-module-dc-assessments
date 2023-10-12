<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TagRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentResultBlobValidator;
use OpenEMR\Services\PatientService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

class AssessmentResultRestController implements IRestController
{
    public function __construct(private SystemLogger $logger, private AssignmentCompleter $assignmentCompleter)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        $resultRepo = new AssessmentResultRepository();
        $query = $request->getQueryParams();
        $assessmentUID = $query['assessmentUID'] ?? null;
        $clientId = $query['clientID'] ?? null;
        $resultId = $query['resultID'] ?? $query['resultIds'] ?? null;

        // first we grab the client and need to check if the current user even has access to this client
        $patientRepo = new PatientService();
        try {
            $patientData = ProcessingResult::extractDataArray($patientRepo->getOne($clientId));
            if (empty($patientData)) {
                throw new \InvalidArgumentException("Invalid client id");
            } else {
                $patientData = $patientData[0];
                $patientUuid = $patientData['uuid'];
            }
            // have to have one or the other
            if (empty($resultId) && empty($assessmentUID)) {
                throw new \InvalidArgumentException("Missing required query parameter assessmentUID or resultID");
            }

            // now for a bunch of permission checks
            // if we are logged in as a patient, we can only see our own results
            if ($request->getAuthRole() == Role::Client) {
                if ($patientUuid != $request->getPatientUUIDString()) {
                    throw new AccessDeniedException("patient", "demo", "Attempt to view another patient's results");
                }
            } else if (!AclMain::aclCheckCore("patients", "demo")) {
                throw new AccessDeniedException("patients", "demo", "Missing patients/demo ACL");
            }
            // TODO: @adunsulag if users are restricted from seeing this patient we would want to handle that here

            if (is_array($resultId)) {
                $results = $resultRepo->getResultListForPatient($clientId, $resultId);
            } else {
                $results = $resultRepo->getResultsForPatient($clientId, $assessmentUID, $resultId);
            }
            return RestUtils::returnSingleObjectResponse($results);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        return RestUtils::getNotFoundResponse();
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $data = $request->getBodyAsJson();
        $validator = new AssessmentResultBlobValidator();

        $transactionCommitted = false;
        $patientService = new PatientService();
        try {
            QueryUtils::startTransaction();

            $validation = $validator->validate($data, AssessmentResultBlobValidator::DATABASE_INSERT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }
            $client = $this->validateCreateAccessAndReturnClient($request->getUserId(), $request->getPatientUUIDString(), $data['clientId'] ?? null, $patientService);

            $assignmentRepo = new AssignmentRepository();
            $item = $assignmentRepo->getAssignmentItem($data['data']['_assignmentItemId'], UuidRegistry::uuidToString($client['uuid']));
            if (empty($item)) {
                throw new \InvalidArgumentException("Assignment item not found", ErrorCode::INVALID_REQUEST);
            } else if (!($item instanceof AssignedAssessment)) {
                throw new \InvalidArgumentException("Assignment item is not an assessment", ErrorCode::INVALID_REQUEST);
            }

            $item->setResultId($data['id']);

            // now we can insert the result
            $resultRepo = new AssessmentResultRepository();
            $savedResult = $resultRepo->createResult($item->getResultId(), $data, $client['pid'], $item->getAssessmentId());
            // TODO: @adunsulag if we allow external embeds w/o client assignment we would handle that here..


            $updatedItem = $this->assignmentCompleter->markAssignmentComplete($item, $client);
            $savedResult['date'] = $updatedItem->getDateCompleted()->format(DATE_ATOM);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;

            return RestUtils::returnSingleObjectResponse($savedResult);
        } catch (AccessDeniedException $exception) {
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    $this->logger->errorLogCaller("Failed to rollback transaction", ['trace' => $e->getTraceAsString()]);
                }
            }
        }
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }

    public function update(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }

    private function validateCreateAccessAndReturnClient(?int $userId, string $patientUuidString, ?string $clientId, PatientService $patientService)
    {

        // first we check to see if we are working as a patient
        if (empty($patientUuidString) && !AclMain::aclCheckCore('encounters', 'notes', $userId)) {
            throw new AccessDeniedException("encounters", "notes", "You do not have permission to create this result");
        } else if (!empty($patientUuidString)) {
            // need to grab the patient pid from the uuid
            $result = $patientService->getOne($patientUuidString);
            if (!$result->hasData()) {
                throw new \InvalidArgumentException("Patient uuid in request does not exist", ErrorCode::SYSTEM_ERROR);
            } else {
                $client = $result->getData()[0];
            }
        } else if (empty($clientId)) { // if we are a user and creating results we need a valid client_id
            throw new \InvalidArgumentException("clientId is required", ErrorCode::VALIDATION_FAILED);
        } else {
            $client = ProcessingResult::extractDataArray($patientService->getOne($clientId));
            if (empty($client)) {
                throw new \InvalidArgumentException("Invalid client_id in request", ErrorCode::VALIDATION_FAILED);
            } else {
                $client = $client[0];
            }
        }
        return $client;
    }
}
