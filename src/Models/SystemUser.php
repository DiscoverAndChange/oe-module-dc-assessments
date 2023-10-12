<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class SystemUser implements \JsonSerializable
{
    private string $_password;
    private int $_role;
    private array $_caps;
    private string $_id;
    private string $_username;
    private ?int $_companyID;
    private string $_companyName;
    private string $_companyPrimaryContact;
    private string $_billingCustomerId;
    private string $_firstName;
    private string $_lastName;
    private bool $_enabled;

    public function __construct(string $id, string $username, int $companyID = null)
    {
        $this->_id = $id;
        $this->_username = $username;
        $this->_companyID = $companyID;
        $this->_enabled = true;
        $this->_role = Role::UnAuthenticated;
        $this->_companyPrimaryContact = '';
        $this->_billingCustomerId = '';
        $this->_firstName = '';
        $this->_lastName = '';
        $this->_password = '';
        $this->_caps = [];
    }

    public function getCompanyPrimaryContact(): string
    {
        return $this->_companyPrimaryContact;
    }

    public function setCompanyPrimaryContact(string $v): void
    {
        $this->_companyPrimaryContact = $v;
    }

    public function getCompanyName(): string
    {
        return $this->_companyName;
    }

    public function setCompanyName(string $v): void
    {
        $this->_companyName = $v;
    }

    public function getEnabled(): bool
    {
        return $this->_enabled;
    }

    public function setEnabled(bool $value): void
    {
        $this->_enabled = $value;
    }

    public function getFirstName(): string
    {
        return $this->_firstName;
    }

    public function setFirstName(string $value): void
    {
        $this->_firstName = $value;
    }

    public function getLastName(): string
    {
        return $this->_lastName;
    }

    public function setLastName(string $value): void
    {
        $this->_lastName = $value;
    }

    public function getPassword(): string
    {
        return $this->_password;
    }

    public function setPassword(string $value): void
    {
        $this->_password = $value;
    }

    public function getRole(): int
    {
        return $this->_role;
    }

    public function setRole(int $value): void
    {
        // use our min and max boundaries to validate this.
        if ($value >= Role::SuperUser && $value <= Role::UnAuthenticated) {
            $this->_role = $value;
        } else {
            throw new \InvalidArgumentException("Invalid role value");
        }
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function setId(string $value): void
    {
        $this->_id = $value;
    }

    public function getUsername(): string
    {
        return $this->_username;
    }

    public function setUsername(string $value): void
    {
        $this->_username = $value;
    }

    public function getCompanyID(): ?int
    {
        return $this->_companyID;
    }

    public function setCompanyID(?int $value): void
    {
        $this->_companyID = $value;
    }

    public function getBillingCustomerId(): string
    {
        return $this->_billingCustomerId;
    }

    public function setBillingCustomerId(string $value): void
    {
        $this->_billingCustomerId = $value;
    }

    public function getCapabilities(): array
    {
        return $this->_caps;
    }

    public function setCapabilities(array $v): void
    {
        $this->_caps = $v;
    }

    public function hasCapability(?int $cap): bool
    {
        return $this->_caps && in_array($cap, $this->_caps);
    }

    public static function fromJSON(object $obj): SystemUser
    {
        if (!is_object($obj)) {
            throw new Exception("Passed in object is not a JSON object");
        }
        $user = new SystemUser($obj->_id, $obj->_username, $obj->_companyID);
        $user->setRole($obj->_role);

        // We do not hydrate caps or password
        return $user;
    }
    public function jsonSerialize()
    {
        return [
            '_id' => $this->_id,
            '_username' => $this->_username,
            '_companyID' => $this->_companyID,
            '_role' => $this->_role,
            '_firstName' => $this->_firstName,
            '_lastName' => $this->_lastName,
            '_enabled' => $this->_enabled,
            '_companyName' => $this->_companyName,
            '_companyPrimaryContact' => $this->_companyPrimaryContact,
            '_billingCustomerId' => $this->_billingCustomerId,
            '_caps' => $this->_caps
        ];
    }
}
