<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSnippet;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentGroupService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentGroupValidator;
use OpenEMR\Services\DocumentTemplates\DocumentTemplateService;
use OpenEMR\Services\FacilityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class AssessmentGroupRestController implements IRestController
{
    public function __construct()
    {
        $this->logger = new SystemLogger();
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $showAllGroups = ($params['showAllGroups'] ?? false) === true || ($params['showAllGroups'] ?? false) === 'true';
        if ($request->getAuthRole() > Role::SuperUser) {
            $showAllGroups = false;
        }
        $facilityService = new FacilityService();
        $primaryFacility = $facilityService->getPrimaryBusinessEntity();
        $groupService = new AssessmentGroupService();
        $results = $groupService->getAllGroups($showAllGroups, $primaryFacility['id']);

        if (empty($results)) {
            return RestUtils::getEmptyResponse();
        } else {
            $groups = $this->createAssessmentGroupsFromEntities($results, $showAllGroups, $this->logger);

            $documentTemplateService = new DocumentTemplateService();
            $profiles = $documentTemplateService->fetchDefaultProfiles();
            $profilesAsGroups = $this->mapProfilesToGroups($documentTemplateService, $profiles);
            $returnGroups = array_merge($groups, $profilesAsGroups);

            $psrFactory = new Psr17Factory();
            return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($returnGroups)));
        }
    }

    private function createAssessmentGroupsFromEntities($results, $showAllGroups, LoggerInterface $logger)
    {
        $groups = [];
        foreach ($results as $result) {
            $group = new AssessmentGroup();
            $group->setName($result['name']);
            $group->setId(intval($result['id']));
            if (!empty($result['date_created'])) {
                $group->setCreated(\DateTime::createFromFormat('Y-m-d H:i:s.u', $result['date_created']));
            }
            if (!empty($result['date_updated'])) {
                $group->setUpdated(\DateTime::createFromFormat('Y-m-d H:i:s.u', $result['date_updated']));
            }
            if ($showAllGroups && !empty($result['company'])) {
                $group->setCompanyId($result['company']['id']);
            }
            foreach ($result['assessmentGroupAssessmentBlobs'] as $agab) {
                if (empty($agab['assessmentBlob'])) {
                    $logger->error("AssessmentGroupAssessmentBlob has no AssessmentBlob entry for group ", ["group" => "group"]);
                    return;
                }
                $snippet = new AssessmentSnippet();
                $snippet->setName($agab['assessmentBlob']['name']);
                $snippet->setId($agab['assessmentBlob']['id']);
                $snippet->setUid($agab['assessmentBlob']['uid']);
                $group->addAssessmentSnippet($snippet);
            }
            $groups[] = $group;
        }
        return $groups;
    }

    public function one(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement one() method.
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_INSERT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }
            $companyId = $request->getAuthRole() == Role::SuperUser ? null : $request->getCompanyId();
            $createdGroup = $repo->createGroup($data['name'], $companyId);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse($createdGroup);
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

    public function update(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement update() method.
    }

    public function addAssessmentToGroup(ServerRestRequest $request, $groupId): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $data['groupId'] = $groupId;
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }
            $uid = $data['uid'];
            $companyId = $request->getAuthRole() == Role::SuperUser ? null : $request->getCompanyId();
            $createdGroup = $repo->addAssessmentToGroup($uid, $groupId, $companyId);
            $assessmentGroups = $this->createAssessmentGroupsFromEntities([$createdGroup], true, $this->logger);
            if (!empty($assessmentGroups)) {
                QueryUtils::commitTransaction();
                $transactionCommitted = true;
                return RestUtils::returnSingleObjectResponse($assessmentGroups[0]);
            } else {
                throw new \Exception("Failed to create JSON object from created group");
            }
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

    public function updateAssessmentVersionForGroup(ServerRestRequest $request, $groupId): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $data['groupId'] = $groupId;
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_UPDATE_ASSESSMENT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }
            $createdGroup = $repo->updateAssessmentVersionForGroup($groupId);
            $assessmentGroups = $this->createAssessmentGroupsFromEntities([$createdGroup], true, $this->logger);
            if (!empty($assessmentGroups)) {
                QueryUtils::commitTransaction();
                $transactionCommitted = true;
                return RestUtils::returnSingleObjectResponse($assessmentGroups[0]);
            } else {
                throw new \Exception("Failed to create JSON object from created group");
            }
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

    private function mapProfilesToGroups(DocumentTemplateService $documentTemplateService, array $profiles)
    {
        $groups = [];
        foreach ($profiles as $profile) {
            $group = new AssessmentGroup();
            $group->setId($profile['option_id']);
            $group->setProfileId($profile['option_id']);
            $group->setName($profile['title']);
            $templates = $documentTemplateService->getTemplateListByProfile($profile['option_id']) ?? [];
            // if a profile has no templates, we don't want to work with it.
            if (!empty($templates)) {
                $templateItems = [];
                // we don't need to show categories in this breakdown
                foreach ($templates as $category => $templates) {
                    if ($category !== 'questionnaire' && $category !== 'Questionnaires') {
                        continue;
                    }

                    foreach ($templates as $template) {
                        $item = new AssessmentSnippet();
                        $item->setId($template['id']);
                        $item->setName($template['template_name']);
                        $item->setUid($template['id']);
                        $templateItems[] = $item;
                    }
                }
                $group->setAssessments($templateItems);
                $groups[] = $group;
            }
        }
        return $groups;
    }
}
