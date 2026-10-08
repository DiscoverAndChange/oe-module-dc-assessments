<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\SystemUserRestController;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for SystemUserRestController against the oe-test-db harness (the seeded
 * user id=1). list() parses pagination from the request URI query and returns the users
 * via SystemUserRepository; one() matches by fhir id (== username).
 *
 * This also exercises the v0.12.4 getUri() fix: list() calls $request->getUri()->getQuery(),
 * which previously TypeErrored.
 */
class SystemUserRestControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Production users always have a uuid (OpenEMR assigns them); the bare test-db
        // seed does not, and SystemUserRepository::getUsers() constructs SystemUser from
        // the uuid. Mirror the production invariant so getUsers() has real data.
        UuidRegistry::createMissingUuidsForTables(['users']);
    }

    private function requestWithQuery(string $query): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getUri')->willReturn('/api/v1/assessment-users?' . $query);
        return new ServerRestRequest($inner);
    }

    public function testListReturnsPaginatedUsers(): void
    {
        $response = (new SystemUserRestController())->list($this->requestWithQuery('_limit=10&_offset=0'));

        $this->assertSame(200, $response->getStatusCode());
        /** @var array<string,mixed> $body */
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('results', $body);
        $this->assertIsArray($body['results']);
    }

    public function testOneReturns404ForUnknownUser(): void
    {
        $response = (new SystemUserRestController())->one($this->requestWithQuery(''), 'no-such-user-xyz');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateReturns400(): void
    {
        $this->assertSame(400, (new SystemUserRestController())->create($this->requestWithQuery(''))->getStatusCode());
    }
}
