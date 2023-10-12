<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

use DateTime;

class AssessmentSummary implements \JsonSerializable
{
    public string $uuid;
    public string $uid;
    public string $name;
    public string $description;
    public DateTime $date;
    public bool $isPublic;
    public string $data; // if we have the data, we will return it
    public function __construct()
    {
        $this->date = new DateTime();
    }

    public function jsonSerialize()
    {
        return [
            'uuid' => $this->uuid,
            'uid' => $this->uid,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date->format(DateTime::ATOM),
            'data' => $this->data ?? null,
            'isPublic' => $this->isPublic
        ];
    }
}
