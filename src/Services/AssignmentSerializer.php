<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;

class AssignmentSerializer
{
    /**
     * @return array<mixed>
     */
    public function serialize(Assignment $assignment)
    {
        return $assignment->jsonSerialize();
    }
    /**
     * @param array<mixed> $assignmentJSON
     * @param int $depth
     * @return Assignment
     */
    public function deserialize(array $assignmentJSON, $depth = 0)
    {
        if ($depth > 5) {
            throw new \InvalidArgumentException('Too many levels of nesting in assignment');
        }
        $subItem = null;
        if ($assignmentJSON['type'] == 'AssessmentGroup') {
            $assignment = new AssignedAssessmentGroup();
            $assignment->fromJSON($assignmentJSON);
            if (!empty($assignmentJSON['items'])) {
                /** @var array<mixed> $items */
                $items = $assignmentJSON['items'];
                foreach ($items as $item) {
                    /** @var array<mixed> $item */
                    $assignment->addItem($this->deserialize($item, $depth + 1));
                }
            }
        } else {
            if ($assignmentJSON['type'] == 'LibraryAsset') {
                $assignment = new Assignment();
                $subItem = new AssignedLibraryAsset();
            } else if ($assignmentJSON['type'] == 'Assessment') {
                $assignment = new Assignment();
                $subItem = new AssignedAssessment();
            } else if ($assignmentJSON['type'] == 'Questionnaire') {
                $assignment = new Assignment();
                $subItem = new AssignedQuestionnaire();
            } else {
                throw new \InvalidArgumentException('Invalid assignment type');
            }
            $assignment->fromJSON($assignmentJSON);
            if (!empty($assignmentJSON['items'])) {
                /** @var array<mixed> $items */
                $items = $assignmentJSON['items'];
                /** @var array<mixed> $jsonItem */
                $jsonItem = $items[0];
                $subItem->fromJSON($jsonItem);
                $assignment->addItem($subItem);
            }
        }

        return $assignment;
    }
}
