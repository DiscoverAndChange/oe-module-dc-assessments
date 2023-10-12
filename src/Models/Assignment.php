<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

use DateTime;

class Assignment implements \JsonSerializable
{
    const ASSIGNMENT_TYPES = ["LibraryAsset", "Assessment", "AssessmentGroup", "Questionnaire", "TemplateProfile"];
    /**
     * @var string $id The unique id of the assignment.  This is a UUID.
     */
    private string $id;
    private string $name;
    private ?DateTime $dateAssigned;
    private ?DateTime $dateCompleted;
    private string $type = "Assessment";
    private ?string $clientId;
    private ?string $appointmentId;
    private ?int $auditId;
    /**
     * @var Assignment[]
     */
    private $items = [];

    public function __construct()
    {
        $this->name = "";
        $this->dateCompleted = null;
        $this->dateAssigned = null;
        $this->clientId = null;
        $this->appointmentId = null;
        $this->auditId = null;
    }

    /**
     * @return string|null
     */
    public function getAppointmentId(): ?string
    {
        return $this->appointmentId;
    }

    /**
     * @param string|null $appointmentId
     * @return Assignment
     */
    public function setAppointmentId(?string $appointmentId): Assignment
    {
        $this->appointmentId = $appointmentId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAuditId(): ?int
    {
        return $this->auditId;
    }

    /**
     * @param int|null $auditId
     * @return Assignment
     */
    public function setAuditId(?int $auditId): Assignment
    {
        $this->auditId = $auditId;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $v)
    {
        $this->clientId = $v;
    }

    /**
     * @return Assignment[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemForId($id): ?Assignment
    {
        foreach ($this->items as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }
        return null;
    }

    public function setItems(array $v): void
    {
        $this->items = $v;
    }

    public function addItem(Assignment $assignment)
    {
        $this->items[] = $assignment;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $v): void
    {
        $this->id = $v;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $v): void
    {
        $this->name = $v;
    }

    public function getDateAssigned(): ?DateTime
    {
        return $this->dateAssigned;
    }

    public function setDateAssigned(DateTime $v): void
    {
        $this->dateAssigned = $v;
    }

    public function getDateCompleted(): ?DateTime
    {
        return $this->dateCompleted;
    }

    public function setDateCompleted(DateTime $v): void
    {
        $this->dateCompleted = $v;
    }

    public function isComplete(): bool
    {
        return $this->dateCompleted != null;
    }

    public function setDateAssignedFromTimestamp(int $time): ?DateTime
    {
        $this->dateAssigned = new DateTime();
        $this->dateAssigned->setTimestamp($time);
        return $this->dateAssigned;
    }

    public function getIsComplete(): bool
    {
        return $this->dateCompleted !== null;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isGroupType(): bool
    {
        return $this->type == "AssessmentGroup" || $this->type == "TemplateProfile";
    }

    public function setType(string $v): void
    {
        // type can only be "LibraryAsset", "Assessment", or "AssessmentGroup"
        if (!in_array($v, self::ASSIGNMENT_TYPES)) {
            throw new \InvalidArgumentException("Invalid assignment type");
        }
        $this->type = $v;
    }

    public function jsonSerialize()
    {
        $result = [
            "id" => $this->id,
            "name" => $this->name,
            "dateAssigned" => $this->dateAssigned,
            "dateCompleted" => $this->dateCompleted,
            "type" => $this->type,
            "appointmentId" => $this->appointmentId,
            "items" => []
        ];
        if (!empty($this->getItems())) {
            $result["items"] = array_map(function ($item) {
                return $item->jsonSerialize();
            }, $this->getItems());
        }
        if ($this->dateAssigned !== null) {
            $result["dateAssigned"] = $this->dateAssigned->format(DateTime::ATOM);
        }
        if ($this->dateCompleted !== null) {
            $result["dateCompleted"] = $this->dateCompleted->format(DateTime::ATOM);
        }
        if (!empty($this->getClientId())) {
            $result["clientId"] = $this->getClientId();
        }
        return $result;
    }

    public function fromJSON(array $assignmentJSON)
    {
        $dateFormat = "Y-m-d\TH:i:s.uP";
        $this->setId($assignmentJSON["id"] ?? 0);
        $this->setName($assignmentJSON["name"] ?? "");
        $this->setType($assignmentJSON["type"] ?? "Assessment");
        if (!empty($assignmentJSON['dateCompleted'])) {
            $this->setDateCompleted(\DateTime::createFromFormat($dateFormat, $assignmentJSON['dateCompleted']));
        }
        if (!empty($assignmentJSON['dateAssigned'])) {
            $this->setDateAssigned(\DateTime::createFromFormat($dateFormat, $assignmentJSON['dateAssigned']));
        }
        if (!empty($assignmentJSON['appointmentId'])) {
            $this->setAppointmentId($assignmentJSON['appointmentId']);
        }
        // we skip over items as we can't do much there.
    }
}
