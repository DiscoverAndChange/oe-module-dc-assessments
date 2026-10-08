<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\LibraryAssetResultRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for LibraryAssetResultRestController against the oe-test-db harness.
 *
 * The patient SPA reads a single asset result through one() on the patient (Role::Client)
 * branch: it resolves the patient from the request uuid and then looks the result up scoped
 * to that patient. This test seeds a patient so that branch is reached deterministically
 * (independent of ACL/session state), then asserts an unknown result id yields a 404.
 *
 * list() and update() are deterministic stubs (200 [] and 400 respectively) and are asserted
 * directly. The AssignmentCompleter constructor dependency is only used by create() (which the
 * SPA no longer calls), so it is supplied as a mock.
 *
 * NOTE (reported, not fixed): LibraryAssetResultBlobRepository::getDecryptedAssetResultBlob()
 * does `return $results[0];` on a possibly-empty array, emitting an "Undefined array key 0"
 * warning for a missing id before returning null. The 404 outcome is unaffected (null is
 * "empty" -> getNotFoundResponse) and the module phpunit config does not fail on warnings.
 */
class LibraryAssetResultRestControllerTest extends TestCase
{
    private string $patientUuid = '';

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM patient_data WHERE pubpid LIKE 'phptest-%'",
            [],
            true
        );
    }

    private function controller(): LibraryAssetResultRestController
    {
        return new LibraryAssetResultRestController(
            new SystemLogger(),
            new CryptoGen(),
            $this->createMock(AssignmentCompleter::class)
        );
    }

    /** A plain (non-patient) request; no inner methods are needed for the stub paths. */
    private function plainRequest(): ServerRestRequest
    {
        return new ServerRestRequest($this->createMock(HttpRestRequest::class));
    }

    /** Seed a patient and return a request that presents as that patient (Role::Client). */
    private function patientRequest(): ServerRestRequest
    {
        $uuidBytes = (new UuidRegistry(['disable_tracker' => true]))->createUuid();
        $this->patientUuid = UuidRegistry::uuidToString($uuidBytes);
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO patient_data (uuid, fname, lname, pubpid) VALUES (?, ?, ?, ?)",
            [$uuidBytes, 'phptest', 'phptest', 'phptest-pt-' . bin2hex(random_bytes(4))]
        );

        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('isPatientRequest')->willReturn(true);
        $inner->method('getPatientUUIDString')->willReturn($this->patientUuid);
        return new ServerRestRequest($inner);
    }

    public function testListReturns200EmptyArray(): void
    {
        $response = $this->controller()->list($this->plainRequest());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    public function testUpdateReturns400(): void
    {
        $response = $this->controller()->update($this->plainRequest(), 'any-id');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testOneReturns404ForUnknownResultOnPatientBranch(): void
    {
        $response = $this->controller()->one($this->patientRequest(), 'phptest-no-such-result');
        $this->assertSame(404, $response->getStatusCode());
    }
}
