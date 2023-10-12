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

    public function addTemplateProfileAssignmentToClient($clientId, $profileId, $userId, $facilityId, $appointmentId = null)
    {
        $assignmentRepository = new AssignmentRepository();

        // need to make sure we actually have a template id
        // else we create a new assignment
        if (empty($clientId)) {
            throw new \InvalidArgumentException("No uuid found for patient pid " . $clientId);
        }
        $listService = new ListService();
        $listOption = $listService->getListOption(AssignmentRepository::TEMPLATE_PROFILE_LIST_ID, trim($profileId));
        if (empty($listOption)) {
            throw new \InvalidArgumentException("No list option found for profile id {$profileId}");
        }
        return $assignmentRepository->createClientAssignmentForProfile($clientId, $appointmentId, $listOption['title'], $listOption['option_id'], $userId);
    }

    public function addGroupAssignmentToClient($clientId, $groupId, $userId, $facilityId, $appointmentId = null)
    {
        $dateAssigned = new \DateTime();
        $assignment = new AssignedAssessmentGroup();
        $assignment->setDateAssigned($dateAssigned);
        $assignment->setAssessmentGroupId($groupId);
        if (!empty($appointmentId)) {
            $assignment->setAppointmentId($appointmentId);
        }

        $assessmentGroupService = new AssessmentGroupService();
        $group = $assessmentGroupService->getGroup($groupId);
        if (empty($group)) {
            throw new \InvalidArgumentException("Invalid group id " . $groupId);
        }
        $assignment->setName($group['name']);
        foreach ($group['assessmentGroupAssessmentBlobs'] as $blob) {
            $assignmentItem = new AssignedAssessment();
            $assignmentItem->setDateAssigned($dateAssigned);
            $assignmentItem->setName($blob['assessmentBlob']['name']);
            $assignmentItem->setUid($blob['assessmentBlob']['uid']);
            $assignmentItem->setAssessmentId($blob['assessmentBlob']['id']);
            $assignment->addItem($assignmentItem);
        }
        $assignmentRepository = new AssignmentRepository();
        $updatedAssignment = $assignmentRepository->saveAssignmentForClient($clientId, $assignment, $userId);
        return $updatedAssignment;
    }

    public function removeAssignmentFromClient($clientId, $assignmentId, int $userId, ?int $facilityId)
    {
        // need to check if the user has permission to remove the assignment
        $assignmentRepository = new AssignmentRepository();
        return $assignmentRepository->removeAssignment($clientId, $assignmentId, $userId);
    }

    public function addAssignmentToClient($clientId, Assignment $assignment, int $getUserId): Assignment
    {
        if (empty($assignment->getItems())) {
            throw new \InvalidArgumentException("Assignment must have at least one item");
        }
        $item = $assignment->getItems()[0];
        $dateAssigned = new \DateTime();
        $assignment->setDateAssigned($dateAssigned);
        $item->setDateAssigned($dateAssigned);

        if ($item instanceof AssignedAssessment) {
            if (empty($item->getUid())) {
                throw new \InvalidArgumentException("Assessment id must be set");
            } else {
                $assessmentRepo = new AssessmentRepository($this->logger);
                $assessmentId = $assessmentRepo->getMostRecentAssessmentIdForUid($item->getUid());
                $item->setAssessmentId($assessmentId);
            }
        }
        if ($item instanceof AssignedLibraryAsset && empty($item->getAssetId())) {
            throw new \InvalidArgumentException("Asset id must be set");
        }
        $assignmentRepository = new AssignmentRepository();
        $updatedAssignment = $assignmentRepository->saveAssignmentForClient($clientId, $assignment, $getUserId);
        return $updatedAssignment;
    }
}
