<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for Client hydration/serialization and
 * assignment handling. Locks in the array<->object behavior the upcoming type
 * refactor will rewrite.
 */
class ClientTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $c = new Client();

        $this->assertSame('', $c->getId());
        $this->assertSame('', $c->getCustomField1());
        $this->assertSame([], $c->getAssignments());
        $this->assertNull($c->getFirstName());
        $this->assertNull($c->getLastName());
        $this->assertNull($c->getEmail());
        $this->assertNull($c->getAssignedUser());
    }

    public function testScalarSettersAndGetters(): void
    {
        $c = new Client();
        $c->setId('client-1');
        $c->setCustomField1('cf');
        $c->setCompanyID(7);
        $c->setFirstName('Ada');
        $c->setLastName('Lovelace');
        $c->setEmail('ada@example.com');

        $this->assertSame('client-1', $c->getId());
        $this->assertSame('cf', $c->getCustomField1());
        $this->assertSame(7, $c->getCompanyID());
        $this->assertSame('Ada', $c->getFirstName());
        $this->assertSame('Lovelace', $c->getLastName());
        $this->assertSame('ada@example.com', $c->getEmail());
    }

    public function testGetDisplayNameJoinsFirstAndLast(): void
    {
        $c = new Client();
        $c->setFirstName('Ada');
        $c->setLastName('Lovelace');
        $this->assertSame('Ada Lovelace', $c->getDisplayName());
    }

    public function testGetDisplayNameWithNullNamesIsJustASpace(): void
    {
        $c = new Client();
        // both names default to null -> "" . " " . "" => " "
        $this->assertSame(' ', $c->getDisplayName());
    }

    public function testAddAssignmentAppends(): void
    {
        $c = new Client();
        $a = new Assignment();
        $a->setId('a-1');
        $a->setName('n');

        $c->addAssignment($a);

        $this->assertCount(1, $c->getAssignments());
        $this->assertSame('a-1', $c->getAssignments()[0]->getId());
    }

    public function testSetAssignmentsReplacesCollection(): void
    {
        $c = new Client();
        $a1 = new Assignment();
        $a1->setId('a-1');
        $a2 = new Assignment();
        $a2->setId('a-2');

        $c->setAssignments([$a1, $a2]);

        $this->assertCount(2, $c->getAssignments());
    }

    public function testSortAssignmentsByDateAssignedOrdersAscending(): void
    {
        $c = new Client();

        $late = new Assignment();
        $late->setId('late');
        $late->setDateAssigned(new \DateTime('2026-03-01T00:00:00+00:00'));

        $early = new Assignment();
        $early->setId('early');
        $early->setDateAssigned(new \DateTime('2026-01-01T00:00:00+00:00'));

        $mid = new Assignment();
        $mid->setId('mid');
        $mid->setDateAssigned(new \DateTime('2026-02-01T00:00:00+00:00'));

        $c->addAssignment($late);
        $c->addAssignment($early);
        $c->addAssignment($mid);

        $c->sortAssignmentsByDateAssigned();

        $ids = array_map(static fn(Assignment $a) => $a->getId(), $c->getAssignments());
        $this->assertSame(['early', 'mid', 'late'], $ids);
    }

    public function testJsonSerializeIncludesSetScalarsAndOmitsUninitializedCompanyId(): void
    {
        $c = new Client();
        $c->setId('client-1');
        $c->setCustomField1('cf');
        $c->setFirstName('Ada');

        $out = $c->jsonSerialize();

        $this->assertSame('client-1', $out['id']);
        $this->assertSame('cf', $out['customField1']);
        $this->assertSame('Ada', $out['firstName']);
        $this->assertSame([], $out['assignments']);
        // assignedUser is null -> kept as null (empty check leaves the null in place)
        $this->assertArrayHasKey('assignedUser', $out);
        $this->assertNull($out['assignedUser']);
        // companyId is a typed property with no default and was never set, so
        // get_object_vars() omits it entirely.
        $this->assertArrayNotHasKey('companyId', $out);
    }

    public function testJsonSerializeSerializesAssignedUser(): void
    {
        $c = new Client();
        $c->setId('client-1');

        $user = new SystemUser('u-1', 'bob', 5);
        $user->setCompanyName('Acme'); // required: jsonSerialize() reads _companyName directly
        $c->setAssignedUser($user);

        $out = $c->jsonSerialize();

        $this->assertIsArray($out['assignedUser']);
        $this->assertSame('u-1', $out['assignedUser']['_id']);
        $this->assertSame('bob', $out['assignedUser']['_username']);
    }

    /**
     * KNOWN BUG (see TEST-PLAN.md findings): Client::fromJSON() does
     * array_merge($client, (array) $obj) where $client is a Client *object*, so
     * every call throws a TypeError before any hydration — the method is dead.
     * Marked incomplete rather than asserting the crash as "expected"; once the
     * method is rewritten (source-typing phase), replace this with real
     * round-trip assertions.
     */
    public function testFromJsonHydratesFromObject(): void
    {
        $this->markTestIncomplete(
            'Client::fromJSON() is broken (array_merge on an object -> TypeError). '
            . 'Rewrite the method, then assert real hydration here.'
        );
    }
}
