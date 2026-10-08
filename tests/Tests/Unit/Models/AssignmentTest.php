<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for Assignment hydration/serialization.
 * These lock in the array<->object behavior that the PHPStan source-typing pass
 * rewrites, so a mistyped record or wrong cast is caught before it ships.
 */
class AssignmentTest extends TestCase
{
    public function testFromJsonPopulatesScalarFields(): void
    {
        $a = new Assignment();
        $a->fromJSON([
            'id' => 'uuid-123',
            'name' => 'Intake',
            'type' => 'Assessment',
            'appointmentId' => '42',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'dateCompleted' => '2026-01-03T06:07:08.000000+00:00',
        ]);

        $this->assertSame('uuid-123', $a->getId());
        $this->assertSame('Intake', $a->getName());
        $this->assertSame('Assessment', $a->getType());
        $this->assertSame('42', $a->getAppointmentId());
        $this->assertInstanceOf(\DateTime::class, $a->getDateAssigned());
        $this->assertSame('2026-01-02', $a->getDateAssigned()->format('Y-m-d'));
        $this->assertTrue($a->getIsComplete(), 'dateCompleted present => complete');
    }

    public function testFromJsonLeavesDatesNullWhenAbsent(): void
    {
        $a = new Assignment();
        $a->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'Questionnaire']);

        $this->assertNull($a->getDateAssigned());
        $this->assertNull($a->getDateCompleted());
        $this->assertFalse($a->getIsComplete());
    }

    public function testJsonSerializeRoundTripsDatesAsAtomAndOmitsNullClientId(): void
    {
        $a = new Assignment();
        $a->setId('x1');
        $a->setName('n');
        $a->setType('Questionnaire');
        $a->setDateAssigned(new \DateTime('2026-05-06T07:08:09+00:00'));

        $out = $a->jsonSerialize();

        $this->assertSame('x1', $out['id']);
        $this->assertSame('Questionnaire', $out['type']);
        $this->assertSame([], $out['items']);
        $this->assertArrayNotHasKey('clientId', $out, 'null clientId is omitted');
        $this->assertStringContainsString('2026-05-06', (string) $out['dateAssigned']);
    }

    public function testJsonSerializeIncludesClientIdWhenSet(): void
    {
        $a = new Assignment();
        $a->setId('x2');
        $a->setName('n');
        $a->setClientId('client-9');

        $this->assertSame('client-9', $a->jsonSerialize()['clientId']);
    }

    public function testSetTypeRejectsUnknownType(): void
    {
        $a = new Assignment();
        $this->expectException(\InvalidArgumentException::class);
        $a->setType('NotAType');
    }

    public function testDefaultTypeIsAssessment(): void
    {
        $this->assertSame('Assessment', (new Assignment())->getType());
    }

    public function testAddItemAndGetItems(): void
    {
        $parent = new Assignment();
        $child = new Assignment();
        $child->setId('child-1');
        $child->setName('c');
        $parent->addItem($child);

        $items = $parent->getItems();
        $this->assertCount(1, $items);
        $this->assertSame('child-1', $items[0]->getId());
    }
}
