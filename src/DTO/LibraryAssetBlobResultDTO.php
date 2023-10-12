<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\DTO;

use Ramsey\Uuid\Uuid;

class LibraryAssetBlobResultDTO implements \JsonSerializable
{
    private ?string $id = null;
    private ?int $assetId = null;
    private array $answers = [];
    private ?string $assignmentItemId = null;
    private ?string $journal = null;
    private ?string $clientId = null;
    private \DateTime $creationDate;

    public function __construct()
    {
        $this->generateId();
        $this->setCreationDate(new \DateTime());
    }


    public function generateId()
    {
        $this->setId(Uuid::uuid4()->toString());
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     * @return LibraryAssetBlobResultDTO
     */
    public function setId(?string $id): LibraryAssetBlobResultDTO
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param array $answers
     * @return LibraryAssetBlobResultDTO
     */
    public function setAnswers(array $answers): LibraryAssetBlobResultDTO
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAssignmentItemId(): ?string
    {
        return $this->assignmentItemId;
    }

    /**
     * @param string|null $assignmentItemId
     * @return LibraryAssetBlobResultDTO
     */
    public function setAssignmentItemId(?string $assignmentItemId): LibraryAssetBlobResultDTO
    {
        $this->assignmentItemId = $assignmentItemId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getJournal(): ?string
    {
        return $this->journal;
    }

    /**
     * @param string|null $journal
     * @return LibraryAssetBlobResultDTO
     */
    public function setJournal(?string $journal): LibraryAssetBlobResultDTO
    {
        $this->journal = $journal;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * @param string|null $clientId
     * @return LibraryAssetBlobResultDTO
     */
    public function setClientId(?string $clientId): LibraryAssetBlobResultDTO
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    /**
     * @param int|null $assetId
     * @return LibraryAssetBlobResultDTO
     */
    public function setAssetId(?int $assetId): LibraryAssetBlobResultDTO
    {
        $this->assetId = $assetId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $dto = [
            'id' => $this->id,
            'answers' => $this->answers,
            'journal' => $this->journal,
            'creationDate' => $this->creationDate->format(DATE_ATOM)
        ];
        return $dto;
    }

    public function fromDTO(array $data)
    {
        $this->setId($data['id'] ?? null);
        $asset = $data['asset'] ?? [];
        $assetId = $asset['id'] ?? null;
        $this->setAssetId($assetId);
        $this->setAnswers($data['answers'] ?? []);
        $this->setAssignmentItemId($data['assignmentItemId'] ?? null);
        $this->setJournal($data['journal'] ?? '');
        $this->setClientId($data['clientId'] ?? null);
        $this->setCreationDate($data['creationDate'] ?? new \DateTime());
    }
}
