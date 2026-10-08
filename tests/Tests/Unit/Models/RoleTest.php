<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for the Role constant enum and its
 * isValidRole() membership check. Locks in the numeric mapping and valid range.
 */
class RoleTest extends TestCase
{
    public function testConstantValues(): void
    {
        $this->assertSame(1, Role::SuperUser);
        $this->assertSame(2, Role::Owner);
        $this->assertSame(3, Role::Admin);
        $this->assertSame(4, Role::Registered);
        $this->assertSame(5, Role::Client);
        $this->assertSame(6, Role::UnAuthenticated);
    }

    public function testIsValidRoleAcceptsEveryDefinedRole(): void
    {
        $this->assertTrue(Role::isValidRole(Role::SuperUser));
        $this->assertTrue(Role::isValidRole(Role::Owner));
        $this->assertTrue(Role::isValidRole(Role::Admin));
        $this->assertTrue(Role::isValidRole(Role::Registered));
        $this->assertTrue(Role::isValidRole(Role::Client));
        $this->assertTrue(Role::isValidRole(Role::UnAuthenticated));
    }

    public function testIsValidRoleRejectsBelowRange(): void
    {
        $this->assertFalse(Role::isValidRole(0));
    }

    public function testIsValidRoleRejectsAboveRange(): void
    {
        $this->assertFalse(Role::isValidRole(7));
    }

    public function testIsValidRoleRejectsNegativeValue(): void
    {
        $this->assertFalse(Role::isValidRole(-1));
    }

    public function testIsValidRoleBoundaryValues(): void
    {
        // First and last valid values map directly to SuperUser and UnAuthenticated.
        $this->assertTrue(Role::isValidRole(1));
        $this->assertTrue(Role::isValidRole(6));
    }
}
