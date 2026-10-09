<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Services\ListService;

class ClientRepository
{
    public function __construct(private SystemLogger $logger)
    {
    }

    /**
     * @param string $clientId
     * @param string $profileId
     * @param int $userId
     * @param int|null $facilityId
     * @param string|null $appointmentId
     * @return Assignment
     */
    public function addTemplateProfileAssignmentToClient($clientId, $profileId, $userId, $facilityId, $appointmentId = null)
    {
        $assignmentRepository = new AssignmentRepository();

        // need to make sure we actually have a template id
        // else we create a new assignment
        if ($clientId === '') {
            throw new \InvalidArgumentException("No uuid found for patient pid " . $clientId);
        }
        $listService = new ListService();
        /** @var array<string, mixed> $listOption */
        $listOption = $listService->getListOption(AssignmentRepository::TEMPLATE_PROFILE_LIST_ID, trim($profileId));
        if ($listOption === []) {
            throw new \InvalidArgumentException("No list option found for profile id {$profileId}");
        }
        /** @var string $profileTitle */
        $profileTitle = $listOption['title'];
        /** @var string $profileOptionId */
        $profileOptionId = $listOption['option_id'];
        return $assignmentRepository->createClientAssignmentForProfile($clientId, $appointmentId, $profileTitle, $profileOptionId, $userId);
    }

    /**
     * @param string $clientId
     * @param int $groupId
     * @param int $userId
     * @param int|null $facilityId
     * @param string|null $appointmentId
     * @return Assignment
     */
    public function addGroupAssignmentToClient($clientId, $groupId, $userId, $facilityId, $appointmentId = null)
    {
        $dateAssigned = new \DateTime();
        $assignment = new AssignedAssessmentGroup();
        $assignment->setDateAssigned($dateAssigned);
        $assignment->setAssessmentGroupId($groupId);
        if ($appointmentId !== null && $appointmentId !== '') {
            $assignment->setAppointmentId($appointmentId);
        }

        $assessmentGroupService = new AssessmentGroupService();
        /** @var array{name: string, assessmentGroupAssessmentBlobs: list<array{assessmentBlob: array{id: string, name: ?string, uid: string}}>}|null $group */
        $group = $assessmentGroupService->getGroup($groupId);
        if ($group === null) {
            throw new \InvalidArgumentException("Invalid group id " . $groupId);
        }
        $assignment->setName($group['name']);
        foreach ($group['assessmentGroupAssessmentBlobs'] as $blob) {
            $assignmentItem = new AssignedAssessment();
            $assignmentItem->setDateAssigned($dateAssigned);
            $assignmentItem->setName($blob['assessmentBlob']['name'] ?? '');
            $assignmentItem->setUid($blob['assessmentBlob']['uid']);
            $assignmentItem->setAssessmentId((int) $blob['assessmentBlob']['id']);
            $assignment->addItem($assignmentItem);
        }
        $assignmentRepository = new AssignmentRepository();
        $updatedAssignment = $assignmentRepository->saveAssignmentForClient($clientId, $assignment, $userId);
        return $updatedAssignment;
    }

    /**
     * @param string $clientId
     * @param string $assignmentId
     * @return string
     */
    public function removeAssignmentFromClient($clientId, $assignmentId, int $userId, ?int $facilityId)
    {
        // need to check if the user has permission to remove the assignment
        $assignmentRepository = new AssignmentRepository();
        return $assignmentRepository->removeAssignment($clientId, $assignmentId, $userId);
    }

    /** @param string $clientId */
    public function addAssignmentToClient($clientId, Assignment $assignment, int $getUserId): Assignment
    {
        if ($assignment->getItems() === []) {
            throw new \InvalidArgumentException("Assignment must have at least one item");
        }
        $item = $assignment->getItems()[0];
        $dateAssigned = new \DateTime();
        $assignment->setDateAssigned($dateAssigned);
        $item->setDateAssigned($dateAssigned);

        if ($item instanceof AssignedAssessment) {
            if ($item->getUid() === '') {
                throw new \InvalidArgumentException("Assessment id must be set");
            } else {
                $assessmentRepo = new AssessmentRepository($this->logger);
                /** @var int|string|null $assessmentId */
                $assessmentId = $assessmentRepo->getMostRecentAssessmentIdForUid($item->getUid());
                if ($assessmentId === null) {
                    throw new \InvalidArgumentException("No published assessment found for uid " . $item->getUid());
                }
                $item->setAssessmentId((int) $assessmentId);
            }
        }
        if ($item instanceof AssignedLibraryAsset && $item->getAssetId() === 0) {
            throw new \InvalidArgumentException("Asset id must be set");
        }
        $assignmentRepository = new AssignmentRepository();
        $updatedAssignment = $assignmentRepository->saveAssignmentForClient($clientId, $assignment, $getUserId);
        return $updatedAssignment;
    }
}
