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
    private SystemLogger $logger;

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
        /** @var array{id?: int, name?: string}|null $primaryFacility */
        $primaryFacility = $facilityService->getPrimaryBusinessEntity();
        $groupService = new AssessmentGroupService();
        $results = $groupService->getAllGroups($showAllGroups, $primaryFacility['id'] ?? null);

        if (empty($results)) {
            return RestUtils::getEmptyResponse();
        } else {
            $groups = $this->createAssessmentGroupsFromEntities($results, $showAllGroups, $this->logger) ?? [];

            $documentTemplateService = new DocumentTemplateService();
            $profiles = $documentTemplateService->fetchDefaultProfiles();
            $profilesAsGroups = $this->mapProfilesToGroups($documentTemplateService, $profiles);
            $returnGroups = array_merge($groups, $profilesAsGroups);

            $psrFactory = new Psr17Factory();
            return $psrFactory->createResponse(200)->withBody($psrFactory->createStream((string) json_encode($returnGroups)));
        }
    }

    /**
     * @param array<mixed> $results
     * @param bool $showAllGroups
     * @return AssessmentGroup[]|null
     */
    private function createAssessmentGroupsFromEntities($results, $showAllGroups, LoggerInterface $logger)
    {
        $groups = [];
        foreach ($results as $result) {
            /** @var array{id: string, name: string, date_created: ?string, date_updated: ?string, company: array{id: string, name: ?string}|null, assessmentGroupAssessmentBlobs: list<array{assessmentBlob: array{id: string, name: string, uid: string}}>} $result */
            $group = new AssessmentGroup();
            $group->setName($result['name']);
            $group->setId($result['id']);
            if (!empty($result['date_created'])) {
                $group->setCreated(\DateTime::createFromFormat('Y-m-d H:i:s.u', $result['date_created']));
            }
            if (!empty($result['date_updated'])) {
                $group->setUpdated(\DateTime::createFromFormat('Y-m-d H:i:s.u', $result['date_updated']));
            }
            if ($showAllGroups && !empty($result['company'])) {
                $group->setCompanyId((int) $result['company']['id']);
            }
            foreach ($result['assessmentGroupAssessmentBlobs'] as $agab) {
                if (empty($agab['assessmentBlob'])) {
                    $logger->error("AssessmentGroupAssessmentBlob has no AssessmentBlob entry for group ", ["group" => "group"]);
                    return;
                }
                $snippet = new AssessmentSnippet();
                $snippet->setName($agab['assessmentBlob']['name']);
                $snippet->setId((int) $agab['assessmentBlob']['id']);
                $snippet->setUid($agab['assessmentBlob']['uid']);
                $group->addAssessmentSnippet($snippet);
            }
            $groups[] = $group;
        }
        return $groups;
    }

    /**
     * @param string $id
     */
    public function one(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        return RestUtils::getNotFoundResponse();
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            /** @var array<string, mixed> $data */
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_INSERT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->error("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }
            $companyId = $request->getAuthRole() == Role::SuperUser ? null : $request->getCompanyId();
            $createdGroup = $repo->createGroup($data['name'], $companyId);
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse($createdGroup);
        } catch (AccessDeniedException $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    $this->logger->error("Failed to rollback transaction", ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    /**
     * @param string $id
     */
    public function update(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement update() method.
        return RestUtils::getNotFoundResponse();
    }

    /**
     * @param string $groupId
     */
    public function addAssessmentToGroup(ServerRestRequest $request, $groupId): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            /** @var array<string, mixed> $data */
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $data['groupId'] = $groupId;
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->error("Validation failed", ['errors' => $validation->getValidationMessages()]);
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
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    $this->logger->error("Failed to rollback transaction", ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    /**
     * @param string $groupId
     */
    public function updateAssessmentVersionForGroup(ServerRestRequest $request, $groupId): ResponseInterface
    {
        $transactionCommitted = false;
        $validator = new AssessmentGroupValidator();
        $repo = new AssessmentGroupService();
        try {
            /** @var array<string, mixed> $data */
            $data = $request->getBodyAsJson();
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException("encounters", "forms", "Access denied to create this resource");
            }
            QueryUtils::startTransaction();
            $data['groupId'] = $groupId;
            $validation = $validator->validate($data, AssessmentGroupValidator::DATABASE_UPDATE_ASSESSMENT_CONTEXT);

            if (!$validation->isValid()) {
                $this->logger->error("Validation failed", ['errors' => $validation->getValidationMessages()]);
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
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    $this->logger->error("Failed to rollback transaction", ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    /**
     * @param list<array{option_id: string, title: string, seq?: string}> $profiles
     * @return AssessmentGroup[]
     */
    private function mapProfilesToGroups(DocumentTemplateService $documentTemplateService, array $profiles)
    {
        $groups = [];
        foreach ($profiles as $profile) {
            $group = new AssessmentGroup();
            $group->setId($profile['option_id']);
            $group->setProfileId($profile['option_id']);
            $group->setName($profile['title']);
            /** @var array<string, list<array{id: string, template_name: string}>> $templates */
            $templates = $documentTemplateService->getTemplateListByProfile($profile['option_id']);
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
                        $item->setId((int) $template['id']);
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
