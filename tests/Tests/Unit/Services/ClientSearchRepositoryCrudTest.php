<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\ClientSearchQueryDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientSearchRepository;
use OpenEMR\Services\Search\SearchQueryConfig;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed tests for ClientSearchRepository against the oe-test-db harness.
 *
 * searchClientList() delegates to PatientService->search() over patient_data and
 * decorates the matches into Client models. We seed patient_data rows with highly
 * distinctive 'phptest-' first/last name and email values so the CONTAINS/EXACT
 * matches cannot collide with any pre-existing (non-phptest) rows. All seeded rows
 * are removed in tearDown().
 */
class ClientSearchRepositoryCrudTest extends TestCase
{
    private const FNAME = 'phptestClientFnameUnqA';
    private const LNAME = 'phptestClientLnameUnqA';
    private const EMAIL = 'phptest-client-unq-a@example.test';

    private ClientSearchRepository $repo;
    private string $patientUuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ClientSearchRepository(null);

        $pid = (int) QueryUtils::sqlInsert(
            "INSERT INTO patient_data (fname, lname, email, pubpid) VALUES (?, ?, ?, ?)",
            [self::FNAME, self::LNAME, self::EMAIL, 'phptest-client-pub-a']
        );
        QueryUtils::sqlStatementThrowException("UPDATE patient_data SET pid = ? WHERE id = ?", [$pid, $pid]);
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        $uuidBytes = QueryUtils::fetchSingleValue("SELECT uuid FROM patient_data WHERE id = ?", 'uuid', [$pid]);
        $this->patientUuid = UuidRegistry::uuidToString($uuidBytes);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM patient_data WHERE fname LIKE 'phptest%'",
            [],
            true
        );
    }

    private function dto(): ClientSearchQueryDTO
    {
        return new ClientSearchQueryDTO();
    }

    /**
     * @return array<int,Client>
     */
    private function search(ClientSearchQueryDTO $dto): array
    {
        $result = $this->repo->searchClientList($dto, new SearchQueryConfig(), null);
        /** @var array<int,Client> $data */
        $data = $result->getData();
        return $data;
    }

    public function testSearchByFirstAndLastNameExact(): void
    {
        $dto = $this->dto();
        $dto->firstName = self::FNAME;
        $dto->lastName = self::LNAME;
        $dto->exactMatch = true;

        $clients = $this->search($dto);
        $this->assertCount(1, $clients, 'exact first+last name should match exactly the one seeded client');
        $this->assertSame(self::FNAME, $clients[0]->getFirstName());
        $this->assertSame(self::LNAME, $clients[0]->getLastName());
        $this->assertSame($this->patientUuid, $clients[0]->getId());
    }

    public function testSearchByEmailContains(): void
    {
        $dto = $this->dto();
        $dto->email = self::EMAIL;

        $clients = $this->search($dto);
        $this->assertCount(1, $clients);
        $this->assertSame(self::EMAIL, $clients[0]->getEmail());
        $this->assertSame($this->patientUuid, $clients[0]->getId());
    }

    public function testSearchByUuid(): void
    {
        $dto = $this->dto();
        $dto->id = $this->patientUuid;

        $clients = $this->search($dto);
        $this->assertCount(1, $clients);
        $this->assertSame($this->patientUuid, $clients[0]->getId());
        $this->assertSame(self::FNAME, $clients[0]->getFirstName());
    }

    public function testSearchByInvalidUuidReturnsEmpty(): void
    {
        $dto = $this->dto();
        $dto->id = 'not-a-valid-uuid';

        $result = $this->repo->searchClientList($dto, new SearchQueryConfig(), null);
        $this->assertFalse($result->hasData(), 'an invalid uuid short-circuits to an empty result');
        $this->assertSame([], $result->getData());
    }

    public function testSearchByNonexistentNameReturnsEmpty(): void
    {
        $dto = $this->dto();
        $dto->firstName = 'phptestNoSuchFirstNameXYZ';
        $dto->lastName = 'phptestNoSuchLastNameXYZ';
        $dto->exactMatch = true;

        $this->assertSame([], $this->search($dto), 'no matching client => empty data');
    }
}
