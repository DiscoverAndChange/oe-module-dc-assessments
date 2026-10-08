<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use DateTime;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSummary;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentSummary serialization.
 * AssessmentSummary exposes public typed properties and a single jsonSerialize().
 */
class AssessmentSummaryTest extends TestCase
{
    public function testConstructorInitializesDate(): void
    {
        $s = new AssessmentSummary();
        $this->assertInstanceOf(DateTime::class, $s->date);
    }

    public function testJsonSerializeRoundTripWithDataAndAtomDate(): void
    {
        $s = new AssessmentSummary();
        $s->uuid = 'uuid-1';
        $s->uid = 'uid-1';
        $s->name = 'Depression Screen';
        $s->description = 'A screening tool';
        $s->isPublic = true;
        $s->data = '{"q":1}';
        $s->date = new DateTime('2026-04-05T06:07:08+00:00');

        $out = $s->jsonSerialize();

        $this->assertSame('uuid-1', $out['uuid']);
        $this->assertSame('uid-1', $out['uid']);
        $this->assertSame('Depression Screen', $out['name']);
        $this->assertSame('A screening tool', $out['description']);
        $this->assertTrue($out['isPublic']);
        $this->assertSame('{"q":1}', $out['data']);
        $this->assertSame('2026-04-05T06:07:08+00:00', $out['date']);
    }

    public function testJsonSerializeDataIsNullWhenNeverSet(): void
    {
        $s = new AssessmentSummary();
        $s->uuid = 'uuid-1';
        $s->uid = 'uid-1';
        $s->name = 'n';
        $s->description = 'd';
        $s->isPublic = false;
        // $s->data left uninitialized -> "$this->data ?? null" yields null

        $out = $s->jsonSerialize();

        $this->assertArrayHasKey('data', $out);
        $this->assertNull($out['data']);
        $this->assertFalse($out['isPublic']);
    }

    public function testJsonSerializeDateUsesAtomFormat(): void
    {
        $s = new AssessmentSummary();
        $s->uuid = 'u';
        $s->uid = 'u';
        $s->name = 'n';
        $s->description = 'd';
        $s->isPublic = false;
        $s->date = new DateTime('2026-12-31T23:59:59+00:00');

        $out = $s->jsonSerialize();
        $this->assertSame($s->date->format(DateTime::ATOM), $out['date']);
    }
}
