<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

use DateTime;

class AssessmentGroup implements \JsonSerializable
{
    private int|string $id;
    private string $name;
    /**
     * @var AssessmentSnippet[]
     */
    private array $assessments;
    private DateTime $created;
    private DateTime $updated;
    private ?int $clientId;
    private ?int $companyId;
    private ?string $profileId = null;

    public function __construct()
    {
        $this->assessments = [];
        $this->created = new DateTime();
        $this->updated = new DateTime();
        $this->clientId = null;
        $this->companyId = null;
    }
    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function setProfileId(string $v): void
    {
        $this->profileId = $v;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int|string $id): void
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

    public function getAssessments(): array
    {
        return $this->assessments;
    }

    public function setAssessments(array $assessments): void
    {
        $this->assessments = $assessments;
    }

    public function addAssessmentSnippet(AssessmentSnippet $snippet)
    {
        $this->assessments[] = $snippet;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    public function setUpdated(DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function setClientId(?int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id
            ,'name' => $this->name
            ,'profileId' => $this->profileId ?? null
            ,'assessments' => $this->assessments ?? []
            ,'created' => $this->created->format('Y-m-d H:i:s')
            ,'updated' => $this->updated->format('Y-m-d H:i:s')
            ,'clientId' => $this->clientId ?? null
            ,'companyId' => $this->companyId ?? null
        ];
    }
}
