<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use OpenEMR\BC\ServiceContainer;
use Psr\Log\LoggerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentResultBlobValidator;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentValidator;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use Psr\Http\Message\ResponseInterface;

class AssessmentRestController implements IRestController
{
    private LoggerInterface $logger;

    public function __construct(?SystemLogger $logger = null)
    {
        $this->logger = $logger ?? ServiceContainer::getLogger();
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        $facilityRepo = new FacilityService();
        /** @var array{id?: int, name?: string}|null $facility */
        $facility = $facilityRepo->getPrimaryBusinessEntity();
        $facilityId = $facility['id'] ?? null;
        $repo = new AssessmentRepository(ServiceContainer::getLogger());
        $results = $repo->getAssessmentSummaryList($facilityId);
        if ($results === []) {
            return RestUtils::getEmptyResponse();
        } else {
            $psrFactory = new Psr17Factory();
            return $psrFactory->createResponse(200)->withBody($psrFactory->createStream((string) json_encode($results)));
        }
    }

    /**
     * @param string $id
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        try {
            $repo = new AssessmentRepository(ServiceContainer::getLogger());
            $query = $request->getQueryParams();
            $assignmentItemUuid = $query['assignmentItemID'] ?? null;
            $clientID = $query['clientID'] ?? null;
            $version = $query['version'] ?? null;
            $assessment = null;
            if (!is_null($assignmentItemUuid) && UuidRegistry::isValidStringUUID($assignmentItemUuid)) {
                $assessment = $repo->getAssessmentForAssignmentItem($assignmentItemUuid, $id, $clientID);
            } else if (!is_null($version) && $version > 0) {
                $assessment = $repo->getAssessmentForVersion($id, $version);
            } else {
                $assessment = $repo->getAssessmentForUid($id);
            }
            return RestUtils::returnSingleObjectResponse($assessment);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        return $this->createAssessmentForContext($request, AssessmentValidator::DATABASE_INSERT_CONTEXT);
    }

    /**
     * @param string $id
     */
    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        try {
            $facRepo = new FacilityService();
            /** @var array{id?: int, name?: string}|null $primaryBusinessEntity */
            $primaryBusinessEntity = $facRepo->getPrimaryBusinessEntity();
            $companyId = $request->getAuthRole() == Role::SuperUser ? null : ($primaryBusinessEntity['id'] ?? null);
            // first we need to do some checking on whether the current user can edit this assessment
            $assessmentRepo = new AssessmentRepository($this->logger);
            if (!$assessmentRepo->canEditAssessment((int) $id, $companyId)) {
                throw new AccessDeniedException('admin', 'forms', "You do not have permission to edit this assessment");
            }
            return $this->createAssessmentForContext($request, AssessmentValidator::DATABASE_UPDATE_CONTEXT);
        } catch (\Exception $exception) {
            return RestUtils::getErrorResponse($this->logger, $exception);
        }
    }

    /**
     * @param string $context
     * @return ResponseInterface
     */
    private function createAssessmentForContext(ServerRestRequest $request, $context)
    {
        $validator = new AssessmentValidator();
        /** @var array<string, mixed> $data */
        $data = $request->getBodyAsJson();

        $companyRepo = new FacilityService();
        try {
            return QueryUtils::inTransaction(function () use ($request, $validator, $data, $companyRepo, $context) {
                $validation = $validator->validate($data, $context);

                if (!$validation->isValid()) {
                    $this->logger->error("Validation failed", ['errors' => $validation->getValidationMessages()]);
                    throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
                }
                if (!AclMain::aclCheckCore('admin', 'forms')) {
                    throw new AccessDeniedException('admin', 'forms', "You do not have permission to create assessments");
                }
                $uid = $data['_uid'];
                $name = $data['_name'];
                $description = $data['_description'];
                if (isset($data['token']) && $data['token'] !== '') {
                    // cleanup routine
                    unset($data['token']);
                }
                /** @var array{id?: int, name?: string}|null $primaryBusinessEntity */
                $primaryBusinessEntity = $companyRepo->getPrimaryBusinessEntity();
                // super users can create assessments for any company, otherwise we use the primary business entity for now
                // TODO: @adunsulag if we restrict companies down by facility we would handle that here.
                $companyId = $request->getAuthRole() == Role::SuperUser ? null : ($primaryBusinessEntity['id'] ?? null);
                $repo = new AssessmentRepository(ServiceContainer::getLogger());
                // can't have duplicates on an insert
                if ($context == AssessmentValidator::DATABASE_INSERT_CONTEXT && $repo->existsAssessment($uid)) {
                    throw new \InvalidArgumentException("Assessment with uid already exists", ErrorCode::DUP_ENTRY);
                }
                $repo->createAssessment($uid, $name, $description, $data, $companyId);
                return RestUtils::returnSingleObjectResponse([]); // we return nothing as part of the create.
            });
        } catch (AccessDeniedException $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }
}
