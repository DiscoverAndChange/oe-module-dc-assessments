<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;

class AssignmentCompleter
{
    public function __construct(private SystemLogger $logger, private ClientMessageDispatcher $dispatcher, private GlobalConfig $config)
    {
    }

    public function markAssignmentComplete(Assignment $item, array $client)
    {
        if (empty($item->getId())) {
            throw new \InvalidArgumentException("AssignmentItem missing id", ErrorCode::VALIDATE_DATA_MISSING);
        }
        if ($item instanceof AssignedLibraryAsset || $item instanceof AssignedAssessment) {
            if (empty($item->getResultId())) {
                throw new \InvalidArgumentException("AssignmentItem missing resultId", ErrorCode::VALIDATE_DATA_MISSING);
            }
        }
        $updatedItem = $this->markAssignmentItemComplete($item);

        // we wrap in a try as we want the user to be able to continue even if emails don't go out or if the overall
        // assignment is not completed
        try {
            $allAssignmentsComplete = $this->checkIfAllAssignmentCompleted($client['pid']);
            if ($allAssignmentsComplete) {
                $this->dispatchNotifications($client);
            } else {
                $this->logger->debug("Assignment has outstanding incomplete items.  Skipping completion");
            }
        } catch (\Exception $exception) {
            $this->logger->errorLogCaller(
                "Failed to check if all assignments complete - " . $exception->getMessage(),
                ['trace' => $exception->getTraceAsString(), 'pid' => $client['pid']]
            );
        }
        return $updatedItem;
    }

    public function checkIfAllAssignmentCompleted(int $clientId)
    {
        $repo = new AssignmentRepository();
        return $repo->hasCompletedAssignments($clientId);
    }

    private function markAssignmentItemComplete(Assignment $item)
    {
        $repo = new AssignmentRepository();
        return $repo->updateCompletedAssignmentItem($item);
    }

    private function getAssignmentForItem(Assignment $item)
    {
        $repo = new AssignmentRepository();
        return $repo->getAssignmentForItem($item->getId());
    }

    private function dispatchNotifications(array $client)
    {
        $this->dispatcher->sendAssignmentsCompleteNotification($client['uuid'], $client['pid']);
    }
}
