<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class AssignedAssessmentGroup extends Assignment
{
    private ?int $assessmentGroupId = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType("AssessmentGroup");
    }

    public function getAssessmentGroupId(): ?int
    {
        return $this->assessmentGroupId;
    }

    public function setAssessmentGroupId(int $v): void
    {
        $this->assessmentGroupId = $v;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize(); // TODO: Change the autogenerated stub
        $items = array_map(function ($item) {
            return $item->jsonSerialize();
        }, $this->getItems() ?? []);
        return array_merge([
            'assessmentGroupId' => $this->getAssessmentGroupId(),
            'items' => $items
        ], $json);
    }

    public function fromJSON(array $assignmentJSON)
    {
        parent::fromJSON($assignmentJSON);
        $this->setAssessmentGroupId($assignmentJSON['assessmentGroupId'] ?? 0);
    }
}
