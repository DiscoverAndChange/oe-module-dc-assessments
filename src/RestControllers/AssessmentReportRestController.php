<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentReportRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentReportValidator;
use Psr\Http\Message\ResponseInterface;

class AssessmentReportRestController implements IRestController
{
    public function __construct(private SystemLogger $logger)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        $repo = new AssessmentReportRepository();
        // TODO: @adunsulag if we implement multiple hosts, support it here
        $hostSiteId = 1;
        $showAllReports = $request->getQueryParams()['showAllReports'] ?? false;
        $showAllReports = $showAllReports === 'true';
        $reports = $repo->getAll($showAllReports, $hostSiteId);
        return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($reports)));
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $repo = new AssessmentReportRepository();
        $hostSiteId = 1;
        try {
            $report = $repo->getOne($id);
            return RestUtils::returnSingleObjectResponse($report);
        } catch (\Exception $exception) {
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getServerErrorResponse($exception);
        }
        // otherwise we return not found
        return RestUtils::getNotFoundResponse();
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $validator = new AssessmentReportValidator();
        $transactionCommitted = false;
        try {
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();

            $validation = $validator->validate($data, AssessmentReportValidator::DATABASE_INSERT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }

            $assessmentGroupID = null;
            if (!empty($data['linkedGroup'])) {
                $assessmentGroupID = $data['linkedGroup']['id'] ?? null;
                unset($data['linkedGroup']);
            }
            $assessmentUid = $data['assessmentUid'] ?? null;
            $repo = new AssessmentReportRepository();
            $result = $repo->createReport($data['id'], $data['name'], $request->getUserId(), $data, $assessmentGroupID, $assessmentUid);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse($result);
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
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        $validator = new AssessmentReportValidator();
        $transactionCommitted = false;
        try {
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();

            $validation = $validator->validate($data, AssessmentReportValidator::DATABASE_UPDATE_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }

            $assessmentGroupID = null;
            if (!empty($data['linkedGroup'])) {
                $assessmentGroupID = $data['linkedGroup']['id'] ?? null;
                unset($data['linkedGroup']);
            }
            $assessmentUid = $data['assessmentUid'] ?? null;
            $repo = new AssessmentReportRepository();
            $result = $repo->updateReport($data['id'], $data['name'], $request->getUserId(), $data, $assessmentGroupID, $assessmentUid);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse([]);
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
    }
}
