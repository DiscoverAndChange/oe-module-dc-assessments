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

    /**
     * @param array<mixed> $client
     * @return mixed
     */
    public function markAssignmentComplete(Assignment $item, array $client)
    {
        if ($item->getId() === '') {
            throw new \InvalidArgumentException("AssignmentItem missing id", ErrorCode::VALIDATE_DATA_MISSING);
        }
        if ($item instanceof AssignedLibraryAsset || $item instanceof AssignedAssessment) {
            $resultId = $item->getResultId();
            if ($resultId === null || $resultId === '') {
                throw new \InvalidArgumentException("AssignmentItem missing resultId", ErrorCode::VALIDATE_DATA_MISSING);
            }
        }
        $updatedItem = $this->markAssignmentItemComplete($item);

        // we wrap in a try as we want the user to be able to continue even if emails don't go out or if the overall
        // assignment is not completed
        try {
            /** @var int|string $clientPid */
            $clientPid = $client['pid'];
            $allAssignmentsComplete = $this->checkIfAllAssignmentCompleted((int) $clientPid);
            if ($allAssignmentsComplete) {
                $this->dispatchNotifications($client);
            } else {
                $this->logger->debug("Assignment has outstanding incomplete items.  Skipping completion");
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                "Failed to check if all assignments complete - " . $exception->getMessage(),
                ['trace' => $exception->getTraceAsString(), 'pid' => $client['pid']]
            );
        }
        return $updatedItem;
    }

    /** @return mixed */
    public function checkIfAllAssignmentCompleted(int $clientId)
    {
        $repo = new AssignmentRepository();
        return $repo->hasCompletedAssignments($clientId);
    }

    /** @return mixed */
    private function markAssignmentItemComplete(Assignment $item)
    {
        $repo = new AssignmentRepository();
        return $repo->updateCompletedAssignmentItem($item);
    }

    /**
     * @param array<mixed> $client
     * @return void
     */
    private function dispatchNotifications(array $client)
    {
        /** @var string $clientUuid */
        $clientUuid = $client['uuid'];
        /** @var int|string $clientPid */
        $clientPid = $client['pid'];
        $this->dispatcher->sendAssignmentsCompleteNotification($clientUuid, (int) $clientPid);
    }
}
