<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\SystemUserRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SystemUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Regression for the SMART-app crash: launching "Patient Portal Assignments" hit
 * GET /api/assessment-users/:uuid -> SystemUserRestController::one -> getUsers(), which threw
 * "SystemUser::__construct(): Argument #2 (\$username) must be of type string, null given".
 *
 * Cause: OpenEMR's users table also holds non-login "address book" entries (external
 * providers) whose username is NULL. getUsers() iterated every user and fed that NULL into
 * the non-null SystemUser::\$username. getUsers() now skips username-less rows (and
 * hydrateUser() coalesces defensively), so the endpoint no longer crashes.
 */
class SystemUserRepositoryAddressBookTest extends TestCase
{
    private int $loginUserId = 0;
    private int $addressBookUserId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // a real login user (has a username)
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO users (username, fname, lname, active) VALUES ('phptest-login-user', 'phptest', 'Login', 1)"
        );
        $this->loginUserId = (int) QueryUtils::fetchSingleValue("SELECT id FROM users WHERE username = 'phptest-login-user' ORDER BY id DESC LIMIT 1", 'id', []);
        // a non-login "address book" user: username NULL (reproduces the crash input)
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO users (username, fname, lname, active) VALUES (NULL, 'phptest-abook', 'Provider', 1)"
        );
        $this->addressBookUserId = (int) QueryUtils::fetchSingleValue("SELECT id FROM users WHERE fname = 'phptest-abook' AND username IS NULL ORDER BY id DESC LIMIT 1", 'id', []);
        // production users always have a uuid
        UuidRegistry::createMissingUuidsForTables(['users']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException("DELETE FROM users WHERE fname LIKE 'phptest%'", [], true);
    }

    private function uuidString(int $id): string
    {
        $bytes = QueryUtils::fetchSingleValue("SELECT uuid FROM users WHERE id = ?", 'uuid', [$id]);
        return UuidRegistry::uuidToString($bytes);
    }

    public function testGetUsersSkipsNullUsernameRowsAndDoesNotThrow(): void
    {
        $repo = new SystemUserRepository();
        $users = $repo->getUsers(); // must not throw despite the NULL-username row

        $ids = array_map(fn(SystemUser $u) => $u->getId(), $users);
        $this->assertContains($this->uuidString($this->loginUserId), $ids, 'the real login user should be listed');
        $this->assertNotContains($this->uuidString($this->addressBookUserId), $ids, 'the NULL-username address-book user should be excluded');
    }

    public function testControllerOneResolvesRealUserWithNullUsernameRowPresent(): void
    {
        $request = new ServerRestRequest($this->createMock(HttpRestRequest::class));
        $response = (new SystemUserRestController())->one($request, $this->uuidString($this->loginUserId));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($this->uuidString($this->loginUserId), $body['_id'] ?? null);
    }
}
