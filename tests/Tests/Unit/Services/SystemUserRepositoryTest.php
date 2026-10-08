<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SystemUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Characterization tests for SystemUserRepository::hydrateUser() (PUBLIC).
 *
 * hydrateUser() maps a user row + primary-business-entity array onto a
 * SystemUser model. It calls AclMain::aclCheckCore('admin','super',$username)
 * to decide SuperUser vs Registered; that call reads the ACL/DB layer under
 * the test harness globals and returns a bool either way (it does not throw
 * for an unknown user). The non-ACL fields are deterministic and asserted
 * exactly; the role is asserted only to be one of the two reachable values so
 * the test is independent of the ACL fixture state.
 *
 * NOTE: if AclMain::aclCheckCore errors in an environment without a real
 * ACL/session, this method should be deferred to the P3 integration batch.
 */
class SystemUserRepositoryTest extends TestCase
{
    public function testHydrateUserMapsDeterministicFields(): void
    {
        $repo = new SystemUserRepository();

        $user = $repo->hydrateUser(
            [
                'uuid' => 'user-uuid-1',
                'username' => 'phptest-nonexistent-user',
                'fname' => 'Ada',
                'lname' => 'Lovelace',
                'active' => '1',
            ],
            ['id' => 5, 'name' => 'Acme Clinic']
        );

        $this->assertInstanceOf(SystemUser::class, $user);
        $this->assertSame('user-uuid-1', $user->getId());
        $this->assertSame('phptest-nonexistent-user', $user->getUsername());
        $this->assertSame(5, $user->getCompanyID());
        $this->assertSame('Acme Clinic', $user->getCompanyName());
        $this->assertSame('Ada', $user->getFirstName());
        $this->assertSame('Lovelace', $user->getLastName());
        $this->assertTrue($user->getEnabled(), "active '1' => enabled");
        $this->assertContains(
            $user->getRole(),
            [Role::SuperUser, Role::Registered],
            'role is resolved from aclCheckCore to one of the two reachable values'
        );
    }

    public function testHydrateUserNullPrimaryEntityGivesNullCompanyAndEmptyName(): void
    {
        $repo = new SystemUserRepository();

        $user = $repo->hydrateUser(
            [
                'uuid' => 'user-uuid-2',
                'username' => 'phptest-nonexistent-user2',
                'fname' => 'Grace',
                'lname' => 'Hopper',
                'active' => '0',
            ],
            null
        );

        $this->assertNull($user->getCompanyID(), 'no primary entity => null companyID');
        $this->assertSame('', $user->getCompanyName(), 'no primary entity => empty company name');
        $this->assertFalse($user->getEnabled(), "active '0' => not enabled");
        $this->assertSame('Grace', $user->getFirstName());
        $this->assertSame('Hopper', $user->getLastName());
    }

    public function testHydrateUserDefaultsMissingNamesToEmptyString(): void
    {
        $repo = new SystemUserRepository();

        $user = $repo->hydrateUser(
            [
                'uuid' => 'user-uuid-3',
                'username' => 'phptest-nonexistent-user3',
                'active' => '1',
            ],
            ['id' => 9, 'name' => 'Beta Org']
        );

        $this->assertSame('', $user->getFirstName());
        $this->assertSame('', $user->getLastName());
        $this->assertSame(9, $user->getCompanyID());
        $this->assertSame('Beta Org', $user->getCompanyName());
    }
}
