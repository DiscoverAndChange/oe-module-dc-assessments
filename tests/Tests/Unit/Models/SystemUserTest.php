<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for SystemUser hydration/serialization
 * and its getters/setters. Locks in behavior ahead of the type refactor.
 */
class SystemUserTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);

        $this->assertSame('u-1', $u->getId());
        $this->assertSame('bob', $u->getUsername());
        $this->assertSame(5, $u->getCompanyID());
        $this->assertTrue($u->getEnabled());
        $this->assertSame(Role::UnAuthenticated, $u->getRole());
        $this->assertSame('', $u->getCompanyPrimaryContact());
        $this->assertSame('', $u->getBillingCustomerId());
        $this->assertSame('', $u->getFirstName());
        $this->assertSame('', $u->getLastName());
        $this->assertSame('', $u->getPassword());
        $this->assertSame([], $u->getCapabilities());
    }

    public function testConstructorCompanyIdDefaultsToNull(): void
    {
        $u = new SystemUser('u-1', 'bob');
        $this->assertNull($u->getCompanyID());
    }

    public function testGettersAndSetters(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $u->setId('u-2');
        $u->setUsername('alice');
        $u->setCompanyID(9);
        $u->setCompanyName('Acme');
        $u->setCompanyPrimaryContact('Jane');
        $u->setBillingCustomerId('cus_123');
        $u->setFirstName('Alice');
        $u->setLastName('Smith');
        $u->setPassword('secret');
        $u->setEnabled(false);
        $u->setRole(Role::Admin);

        $this->assertSame('u-2', $u->getId());
        $this->assertSame('alice', $u->getUsername());
        $this->assertSame(9, $u->getCompanyID());
        $this->assertSame('Acme', $u->getCompanyName());
        $this->assertSame('Jane', $u->getCompanyPrimaryContact());
        $this->assertSame('cus_123', $u->getBillingCustomerId());
        $this->assertSame('Alice', $u->getFirstName());
        $this->assertSame('Smith', $u->getLastName());
        $this->assertSame('secret', $u->getPassword());
        $this->assertFalse($u->getEnabled());
        $this->assertSame(Role::Admin, $u->getRole());
    }

    public function testSetCompanyIdAcceptsNull(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $u->setCompanyID(null);
        $this->assertNull($u->getCompanyID());
    }

    public function testSetRoleAcceptsBoundaryValues(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $u->setRole(Role::SuperUser);
        $this->assertSame(Role::SuperUser, $u->getRole());
        $u->setRole(Role::UnAuthenticated);
        $this->assertSame(Role::UnAuthenticated, $u->getRole());
    }

    public function testSetRoleRejectsBelowRange(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $this->expectException(\InvalidArgumentException::class);
        $u->setRole(0);
    }

    public function testSetRoleRejectsAboveRange(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $this->expectException(\InvalidArgumentException::class);
        $u->setRole(7);
    }

    public function testCapabilities(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $this->assertFalse($u->hasCapability(10));

        $u->setCapabilities([10, 20]);
        $this->assertSame([10, 20], $u->getCapabilities());
        $this->assertTrue($u->hasCapability(10));
        $this->assertTrue($u->hasCapability(20));
        $this->assertFalse($u->hasCapability(30));
    }

    public function testHasCapabilityWithEmptyCapsIsFalse(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        // _caps defaults to [] -> the "&&" short-circuits to a falsy [] result.
        $this->assertFalse($u->hasCapability(null));
    }

    public function testFromJsonPopulatesCoreFields(): void
    {
        $obj = (object) [
            '_id' => 'u-1',
            '_username' => 'bob',
            '_companyID' => 5,
            '_role' => Role::Admin,
        ];

        $u = SystemUser::fromJSON($obj);

        $this->assertInstanceOf(SystemUser::class, $u);
        $this->assertSame('u-1', $u->getId());
        $this->assertSame('bob', $u->getUsername());
        $this->assertSame(5, $u->getCompanyID());
        $this->assertSame(Role::Admin, $u->getRole());
        // caps and password are intentionally NOT hydrated by fromJSON.
        $this->assertSame([], $u->getCapabilities());
        $this->assertSame('', $u->getPassword());
    }

    public function testFromJsonWithoutCompanyIdDefaultsToNull(): void
    {
        $obj = (object) [
            '_id' => 'u-1',
            '_username' => 'bob',
            '_role' => Role::Client,
        ];

        $u = SystemUser::fromJSON($obj);
        $this->assertNull($u->getCompanyID());
        $this->assertSame(Role::Client, $u->getRole());
    }

    public function testFromJsonRejectsInvalidRole(): void
    {
        $obj = (object) [
            '_id' => 'u-1',
            '_username' => 'bob',
            '_companyID' => 5,
            '_role' => 99,
        ];

        $this->expectException(\InvalidArgumentException::class);
        SystemUser::fromJSON($obj);
    }

    public function testJsonSerializeRoundTrip(): void
    {
        $u = new SystemUser('u-1', 'bob', 5);
        $u->setCompanyName('Acme'); // required: serialize reads _companyName directly
        $u->setRole(Role::Owner);
        $u->setFirstName('Bob');
        $u->setLastName('Jones');
        $u->setCompanyPrimaryContact('Jane');
        $u->setBillingCustomerId('cus_1');
        $u->setCapabilities([1, 2]);

        $out = $u->jsonSerialize();

        $this->assertSame('u-1', $out['_id']);
        $this->assertSame('bob', $out['_username']);
        $this->assertSame(5, $out['_companyID']);
        $this->assertSame(Role::Owner, $out['_role']);
        $this->assertSame('Bob', $out['_firstName']);
        $this->assertSame('Jones', $out['_lastName']);
        $this->assertTrue($out['_enabled']);
        $this->assertSame('Acme', $out['_companyName']);
        $this->assertSame('Jane', $out['_companyPrimaryContact']);
        $this->assertSame('cus_1', $out['_billingCustomerId']);
        $this->assertSame([1, 2], $out['_caps']);
        // password is never serialized
        $this->assertArrayNotHasKey('_password', $out);
    }
}
