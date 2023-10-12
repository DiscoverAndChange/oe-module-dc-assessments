<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class AssessmentSnippet implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $uid;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'uid' => $this->getUid()
        ];
    }
}
