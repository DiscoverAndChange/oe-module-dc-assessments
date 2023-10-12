<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class Client implements \JsonSerializable
{
    private string $id = "";
    private array $assignments = [];
    private string $customField1 = "";
    private int $companyId;
    private ?SystemUser $assignedUser = null;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $email = null;

    public function __construct()
    {
        $this->assignments = [];
    }

    public function getDisplayName(): string
    {
        return ($this->firstName ?? "") . " " . ($this->lastName ?? "");
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $v): void
    {
        $this->firstName = $v;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $v): void
    {
        $this->lastName = $v;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $v): void
    {
        $this->email = $v;
    }

    public function getAssignedUser(): ?SystemUser
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?SystemUser $v): void
    {
        $this->assignedUser = $v;
    }

    public function getCompanyID(): int
    {
        return $this->companyId;
    }

    public function setCompanyID(int $v): void
    {
        $this->companyId = $v;
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function setID(string $v): void
    {
        $this->id = $v;
    }

    public function getCustomField1(): string
    {
        return $this->customField1;
    }

    public function setCustomField1(string $v): void
    {
        $this->customField1 = $v;
    }

    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function setAssignments(array $v): void
    {
        $this->assignments = $v;
    }

    public static function fromJSON(object $obj): Client
    {
        if (!is_object($obj)) {
            throw new InvalidArgumentException("Passed in object is not a JSON object");
        }

        $client = new Client();
        $client = array_merge($client, (array) $obj);

        if (isset($obj->assignedUser)) {
            $client->setAssignedUser(SystemUser::fromJSON($obj->assignedUser));
        }

        return $client;
    }

    public function jsonSerialize()
    {
        $properties = get_object_vars($this);
        if (!empty($properties['assignedUser'])) {
            $properties['assignedUser'] = $this->assignedUser->jsonSerialize();
        }
        return $properties;
    }

    public function addAssignment(Assignment $assignment)
    {
        $this->assignments[] = $assignment;
    }

    public function sortAssignmentsByDateAssigned()
    {
        usort($this->assignments, function (Assignment $a, Assignment $b) {
            $aTime = ($a->getDateAssigned() ?? new DateTime());
            $bTime = ($b->getDateAssigned() ?? new DateTime());
            return $aTime < $bTime ? -1 : 1;
        });
    }
}
