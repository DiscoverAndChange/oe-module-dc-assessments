<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Doctrine\ORM\Query;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Cqm\Qdm\BaseTypes\DateTime;
use OpenEMR\Events\Messaging\SendNotificationEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\ClientSearchQueryDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentSerializer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientMessageDispatcher;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientSearchRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTTPResponseUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\PaginatedResultsService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\SearchQueryConfig;
use OpenEMR\Services\UserService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientRestController implements IRestController
{
    public function __construct(private SystemLogger $logger, private ClientMessageDispatcher $messageDispatcher)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        if (!AclMain::aclCheckCore('patients', 'demo')) {
            return RestUtils::returnAccessDeniedResponse($this->logger, 'user missing patients/demo ACL');
        }
        // need to check whether this provider has a relationship with the patient...
        $params = $request->getQueryParams();
        $isAdmin = false;
        if (empty($params['user']) && !($isAdmin = AclMain::aclCheckCore('admin', 'super') == true)) {
            return RestUtils::returnAccessDeniedResponse($this->logger, 'assignedUserId missing and user missing admin/super ACL');
        }

        $searchConfig = SearchQueryConfig::createConfigFromQueryParams($params);
        $userID = $request->getUserId();
        $assignedUserId = $userID; // convert to an actual user id.
        if (isset($params['user'])) {
            $userService = new UserService();
            $user = $userService->getUserByUUID($params['user']);
            if (empty($user) && !$isAdmin) {
                return RestUtils::returnAccessDeniedResponse($this->logger, 'assignedUserId missing and user missing admin/super ACL');
            } else {
                $assignedUserId = $user['id'];
            }
        }
        $facilityRepo = new FacilityService();
        $primaryBusiness = $facilityRepo->getPrimaryBusinessEntity();
        $repo = new ClientSearchRepository($primaryBusiness['id'] ?? null);
        $search = new ClientSearchQueryDTO();
        $search->populateFromRequest($params);
        if ($search->firstName && strlen($search->firstName) < 1) {
            return HTTPResponseUtils::jsonErrorResponseHandler(new SystemError(
                ErrorCode::VALIDATE_DATA_MISSING,
                "First name requires at least 1 character for search"
            ));
        }
        if ($search->lastName && strlen($search->lastName) < 1) {
            return HTTPResponseUtils::jsonErrorResponseHandler(new SystemError(
                ErrorCode::VALIDATE_DATA_MISSING,
                "Last name requires at least 1 character for search"
            ));
        }

        $results = $repo->searchClientList($search, $searchConfig, $assignedUserId);
        return PaginatedResultsService::returnPaginatedResultsForProcessingResponse($results);
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        $params = [];
        $params['id']  = $id;
        $facilityRepo = new FacilityService();
        $primaryBusiness = $facilityRepo->getPrimaryBusinessEntity();
        $repo = new ClientSearchRepository($primaryBusiness['id'] ?? null);
        $search = new ClientSearchQueryDTO();
        $search->populateFromRequest($params);
        $config = new SearchQueryConfig();
        $results = $repo->searchClientList($search, $config, $request->getUserId());
        if ($results->hasData()) {
            $psrFactory = new Psr17Factory();
            return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($results->getData()[0])));
        } else {
            return RestUtils::getNotFoundResponse();
        }
    }

    public function removeAssignmentFromClient(ServerRestRequest $request, $id, $assignmentId)
    {
        $facilityRepo = new FacilityService();
        $primaryBusiness = $facilityRepo->getPrimaryBusinessEntity();
        $transactionCommitted = false;
        try {
            QueryUtils::startTransaction();
            // TODO: @adunsulag is this the best permission for this?
            if (!AclMain::aclCheckCore('patients', 'docs')) {
                throw new AccessDeniedException('user missing admin/super ACL');
            }
            $repo = new ClientRepository($this->logger);
            $id = $repo->removeAssignmentFromClient($id, $assignmentId, $request->getUserId(), $primaryBusiness['id']);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse(['assignmentId' => $id]);
        } catch (AccessDeniedException $exception) {
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::returnAccessDeniedResponse($exception->getMessage());
        } catch (\Exception $exception) {
            // logger is handled in the utils.
            return RestUtils::getErrorResponse($this->logger, $exception);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $exception) {
                    $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
                }
            }
        }
    }

    public function addAssignmentGroupToClient(ServerRestRequest $request, $id)
    {
        $transactionCommitted = false;
        try {
            // these actions from facility break the transaction
            // TODO: @adunsulag need to investigate why both of these function calls break the transaction.
            $facRepo = new FacilityService();
            $facility = $facRepo->getPrimaryBusinessEntity();

            QueryUtils::startTransaction();
//            // TODO: @adunsulag do we want to add separate ACLs for this?
            if (!AclMain::aclCheckCore('patients', 'demo')) {
                throw new AccessDeniedException('patients', 'demo', 'user missing patients/demo ACL for this action');
            }

            $facilityId = $facility['id'] ?? null;

            $group = $request->getBodyAsJson() ?? [];
            $groupId = $group['id'] ?? $group['_id'] ?? null;
            if (empty($groupId)) {
                throw new \InvalidArgumentException('group.id is required');
            }
            $appointmentId = $group['appointmentId'] ?? null;
            $profileId = $group['profileId'] ?? null;
            $repo = new ClientRepository($this->logger);
            if (!empty($profileId)) {
                $createdAssignment = $repo->addTemplateProfileAssignmentToClient($id, $profileId, $request->getUserId(), $facilityId, $appointmentId);
            } else {
                $createdAssignment = $repo->addGroupAssignmentToClient($id, $groupId, $request->getUserId(), $facilityId, $appointmentId);
            }
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse(['assignment' => $createdAssignment]);
        } catch (AccessDeniedException $exception) {
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::returnAccessDeniedResponse($exception->getMessage());
        } catch (\Exception $exception) {
            // logger is handled in the utils.
            return RestUtils::getErrorResponse($this->logger, $exception);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    // if we can't rollback this is really, really bad
                    $this->logger->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    public function addAssignmentToClient(ServerRestRequest $request, $id)
    {
        $transactionCommitted = false;
        try {
            // TODO: @adunsulag do we want to add separate ACLs for this?
            if (!AclMain::aclCheckCore('patients', 'demo')) {
                throw new AccessDeniedException('patients', 'demo', 'user missing patients/demo ACL for this action');
            }
            $assignmentJSON = $request->getBodyAsJson();
            $assignmentSerializer = new AssignmentSerializer();
            $assignment = $assignmentSerializer->deserialize($assignmentJSON);
            QueryUtils::startTransaction();
            $repo = new ClientRepository($this->logger);
            $assignment = $repo->addAssignmentToClient($id, $assignment, $request->getUserId());
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse(['assignment' => $assignment]);
        } catch (AccessDeniedException $exception) {
            return RestUtils::returnAccessDeniedResponse($this->logger, $exception->getMessage());
        } catch (\Exception $exception) {
            // logger is handled in the utils.
            return RestUtils::getErrorResponse($this->logger, $exception);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    // if we can't rollback this is really, really bad
                    $this->logger->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    public function sendMessageToClient(ServerRestRequest $request, $id)
    {
        $transactionCommitted = false;
        $userRepo = new UserService();
        $patientService = new PatientService();
        try {
            $this->logger->debug(self::class . "->sendMessageToClient() called");
            $messageRequest = $request->getBodyAsJson();
            $message = trim($messageRequest['message'] ?? '');
            $subject = trim($messageRequest['subject'] ?? '');
            $isTest = ($messageRequest['isTest'] ?? 0) === 1;

            if (empty($message)) {
                throw new \InvalidArgumentException("message is required", ErrorCode::VALIDATE_DATA_MISSING);
            }
//            if (empty($subject)) {
//                throw new \InvalidArgumentException("subject is required", ErrorCode::VALIDATE_DATA_MISSING);
//            }
            QueryUtils::startTransaction();
            $userId = $request->getUserId();
            $user = $userRepo->getUser($userId);
            if (empty($user)) {
                throw new \InvalidArgumentException("User not found for request", ErrorCode::SYSTEM_ERROR);
            }
            $senderEmail = $user['email'] ?? null;
            $patient = $patientService->getOne($id);
            if (!$patient->hasData()) {
                throw new \InvalidArgumentException("Patient not found for request", ErrorCode::INVALID_REQUEST);
            }
            $patient = ProcessingResult::extractDataArray($patient)[0];
            $patientEmail = $patient['email'] ?? '';
            $this->messageDispatcher->sendInvitationMessage($patient['pid'], $subject, $message, $patientEmail, $senderEmail, $isTest);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
        } catch (\Exception $exception) {
            // logger is handled in the utils.
            return RestUtils::getErrorResponse($this->logger, $exception);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    // if we can't rollback this is really, really bad
                    $this->logger->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }
}
