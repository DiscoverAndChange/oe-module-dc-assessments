<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class AssignedTemplateProfile extends Assignment
{
    private ?string $profileId = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType("TemplateProfile");
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function setProfileId(string $v): void
    {
        $this->profileId = $v;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize(); // TODO: Change the autogenerated stub
        $items = array_map(function ($item) {
            return $item->jsonSerialize();
        }, $this->getItems() ?? []);
        return array_merge([
            'profileId' => $this->getProfileId(),
            'items' => $items
        ], $json);
    }

    public function fromJSON(array $assignmentJSON)
    {
        parent::fromJSON($assignmentJSON);
        $this->setProfileId($assignmentJSON['profileId'] ?? 0);
    }
}
