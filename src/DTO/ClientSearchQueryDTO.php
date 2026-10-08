<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\DTO;

class ClientSearchQueryDTO
{
    // Defaults so isEmpty() is safe to call before populateFromRequest(); previously
    // these uninitialized typed properties threw "must not be accessed before
    // initialization" when isEmpty() ran first.
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $id = null;
    public ?string $email = null;
    public ?bool $exactMatch = null;

    public function isEmpty(): bool
    {
        return !($this->id || $this->email || ($this->firstName && $this->lastName));
    }

    /** @param array<mixed> $request */
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
