<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\DTO;

class LibraryAssetBlobDTO implements \JsonSerializable
{
    private ?int $id = null;

    private ?string $uuid = null;

    private ?string $title = null;
    /**
     * @var "article"|"assignment"
     */
    private string $type;
    private ?string $description = null;
    private ?string $content = null;
    private ?string $journal = null;
    private ?string $originalCreator = null;
    private ?string $creatorLink = null;
    private ?string $creationDate = null;
    private ?string $lastUpdateDate = null;
    private ?array $tags = null;
    private ?array $results = null;

    public function __construct()
    {
        $this->setId(0);
        $this->setUuid("");
        $this->setTitle("");
        $this->setType("article");
        $this->setDescription("");
        $this->setContent("");
        $this->setOriginalCreator("");
        $this->setCreatorLink("");
        $this->setCreationDate((new \DateTime())->format(DATE_ATOM));
        $this->setLastUpdateDate((new \DateTime())->format(DATE_ATOM));
    }

    /**
     * @param string|null $uuid
     */
    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setType(string $type): self
    {
        if ($type === 'article' || $type === 'assignment') {
            $this->type = $type;
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }
        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getJournal(): ?string
    {
        return $this->journal;
    }

    public function setJournal(?string $journal): self
    {
        $this->journal = $journal;
        return $this;
    }

    public function getOriginalCreator(): ?string
    {
        return $this->originalCreator;
    }

    public function setOriginalCreator(?string $originalCreator): self
    {
        $this->originalCreator = $originalCreator;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatorLink(): ?string
    {
        return $this->creatorLink;
    }

    /**
     * @param string|null $creatorLink
     */
    public function setCreatorLink(?string $creatorLink): self
    {
        $this->creatorLink = $creatorLink;
        return $this;
    }


    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    public function setCreationDate(?string $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getLastUpdateDate(): ?string
    {
        return $this->lastUpdateDate;
    }

    public function setLastUpdateDate(?string $lastUpdateDate): self
    {
        $this->lastUpdateDate = $lastUpdateDate;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getResults(): ?array
    {
        return $this->results;
    }

    public function setResults(?array $results): self
    {
        $this->results = $results;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $dto = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'content' => $this->content,
            'journal' => $this->journal,
            'originalCreator' => $this->originalCreator,
            'creationDate' => $this->creationDate,
            'lastUpdateDate' => $this->lastUpdateDate,
            'tags' => $this->tags,
            'results' => $this->results,
        ];
        return $dto;
    }

    public function fromDTO(array $data)
    {
        $this->setId($data['id'] ?? 0);
        $this->setTitle($data['title'] ?? '');
        $this->setType($data['type'] ?? 'article');
        $this->setDescription($data['description'] ?? '');
        $this->setContent($data['content'] ?? '');
        $this->setJournal($data['journal'] ?? null);
        $this->setOriginalCreator($data['originalCreator'] ?? null);
        $this->setCreatorLink($data['creatorLink'] ?? null);
        $this->setCreationDate($data['creationDate'] ?? (new \DateTime())->format(DATE_ATOM));
        $this->setLastUpdateDate($data['lastUpdateDate'] ?? (new \DateTime())->format(DATE_ATOM));
        $this->setTags($data['tags'] ?? []);
        $this->setResults($data['results'] ?? []);
    }
}
