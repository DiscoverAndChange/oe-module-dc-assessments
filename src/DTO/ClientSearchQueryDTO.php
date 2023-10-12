<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\DTO;

class ClientSearchQueryDTO
{
    public ?string $firstName;
    public ?string $lastName;
    public ?string $id;
    public ?string $email;
    public ?bool $exactMatch;

    public function isEmpty(): bool
    {
        return !($this->id || $this->email || ($this->firstName && $this->lastName));
    }

    public function populateFromRequest(array $request): void
    {
        $this->id = $request['id'] ?? null;
        $this->email = $request['email'] ?? null;
        if (!empty($this->email)) {
            $this->email = urldecode($this->email);
        }

        $this->firstName = $request['firstName'] ?? null;
        $this->lastName = $request['lastName'] ?? null;
        $this->exactMatch = ($request['exactMatch'] ?? '') === 'true';
    }
}
