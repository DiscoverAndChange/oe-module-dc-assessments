<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignedLibraryAsset.
 *
 * AssignedLibraryAsset extends Assignment: fromJSON() calls parent::fromJSON()
 * (inherited fields) then sets the subclass fields (assetId/assetUuid).
 * resultId is set through its own setter.
 */
class AssignedLibraryAssetTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $l = new AssignedLibraryAsset();
        $this->assertSame('LibraryAsset', $l->getType());
        $this->assertNull($l->getResultId());
        $this->assertNull($l->getAssetUuid());
    }

    public function testFromJsonPopulatesInheritedAndSubclassFields(): void
    {
        $l = new AssignedLibraryAsset();
        $l->fromJSON([
            'id' => 'uuid-1',
            'name' => 'Handout',
            'type' => 'LibraryAsset',
            'appointmentId' => '8',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'assetId' => 55,
            'assetUuid' => 'asset-uuid-9',
        ]);

        // inherited
        $this->assertSame('uuid-1', $l->getId());
        $this->assertSame('Handout', $l->getName());
        $this->assertSame('LibraryAsset', $l->getType());
        $this->assertSame('8', $l->getAppointmentId());
        $this->assertInstanceOf(\DateTime::class, $l->getDateAssigned());

        // subclass
        $this->assertSame(55, $l->getAssetId());
        $this->assertSame('asset-uuid-9', $l->getAssetUuid());
    }

    public function testFromJsonAssetUuidDefaultsToEmptyStringWhenAbsent(): void
    {
        $l = new AssignedLibraryAsset();
        $l->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'LibraryAsset', 'assetId' => 1]);

        $this->assertSame('', $l->getAssetUuid());
    }

    /**
     * REGRESSION (fixed v0.12.3): with no 'type' key, parent::fromJSON() no longer
     * overwrites the "LibraryAsset" the constructor set.
     */
    public function testFromJsonWithoutTypeKeyPreservesLibraryAssetType(): void
    {
        $l = new AssignedLibraryAsset();
        $l->fromJSON(['id' => 'x', 'name' => 'n', 'assetId' => 1]);

        $this->assertSame('LibraryAsset', $l->getType());
    }

    public function testSetResultIdMarksComplete(): void
    {
        $l = new AssignedLibraryAsset();
        $l->setResultId('r-1');

        $this->assertSame('r-1', $l->getResultId());
        $this->assertTrue($l->getIsComplete());
        $this->assertInstanceOf(\DateTime::class, $l->getDateCompleted());
    }

    public function testJsonSerializeIncludesInheritedAndSubclassKeys(): void
    {
        $l = new AssignedLibraryAsset();
        $l->fromJSON([
            'id' => 'x1',
            'name' => 'RT',
            'type' => 'LibraryAsset',
            'assetId' => 77,
            'assetUuid' => 'au-9',
        ]);
        $l->setResultId('res-9');

        $out = $l->jsonSerialize();

        $this->assertSame('x1', $out['id']);
        $this->assertSame('LibraryAsset', $out['type']);
        $this->assertSame(77, $out['assetId']);
        $this->assertSame('au-9', $out['assetUuid']);
        $this->assertSame('res-9', $out['resultId']);
    }

    public function testJsonSerializeAssetUuidFallsBackToEmptyStringWhenNull(): void
    {
        $l = new AssignedLibraryAsset();
        $l->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'LibraryAsset', 'assetId' => 1, 'assetUuid' => '']);
        $l->setAssetUuid(null);

        $out = $l->jsonSerialize();

        $this->assertSame('', $out['assetUuid']);
        $this->assertNull($out['resultId']);
    }
}
