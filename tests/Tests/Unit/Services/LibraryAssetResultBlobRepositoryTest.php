<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for
 * LibraryAssetResultBlobRepository::hydrateResultBlobFromRecord().
 *
 * shouldEncrypt() always returns true, so the hydrator only calls
 * CryptoGen::decryptStandard() when `answers`/`journal_entry` are non-empty.
 * These tests deliberately craft rows with EMPTY answers/journal so the
 * decrypt calls are skipped entirely and no encryption keys/globals are
 * required. The patient_uuid is supplied as raw 16 bytes so the pure
 * UuidRegistry::uuidToString() path is exercised.
 *
 * DEFERRED to P3: the non-empty answers/journal branch, which runs
 * CryptoGen::decryptStandard() and needs real encryption keys/globals.
 */
class LibraryAssetResultBlobRepositoryTest extends TestCase
{
    /**
     * @param array<string,mixed> $record
     */
    private function hydrate(array $record): LibraryAssetBlobResultDTO
    {
        $repo = new LibraryAssetResultBlobRepository(new SystemLogger(), new CryptoGen());
        $m = new \ReflectionMethod($repo, 'hydrateResultBlobFromRecord');
        $m->setAccessible(true);
        return $m->invoke($repo, $record);
    }

    public function testHydrateMapsFieldsOnPureNoDecryptPath(): void
    {
        $patientBytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-000000000abc');

        $blob = $this->hydrate([
            'id' => 'result-uuid-string',
            'asset_id' => '15',            // string from DB -> cast to int
            'assignmentitem_id' => 'ai-123',
            'answers' => null,             // empty -> decryptStandard NOT called
            'journal_entry' => null,       // empty -> decryptStandard NOT called
            'patient_uuid' => $patientBytes,
            'creation_date' => '2026-01-02 03:04:05.000000',
        ]);

        $this->assertInstanceOf(LibraryAssetBlobResultDTO::class, $blob);
        $this->assertSame('result-uuid-string', $blob->getId());
        $this->assertSame(15, $blob->getAssetId());
        $this->assertSame('ai-123', $blob->getAssignmentItemId());
        $this->assertSame([], $blob->getAnswers(), 'empty answers hydrate to []');
        $this->assertNull($blob->getJournal(), 'empty journal stays null');
        $this->assertSame(UuidRegistry::uuidToString($patientBytes), $blob->getClientId());
        $this->assertInstanceOf(\DateTime::class, $blob->getCreationDate());
        $this->assertSame('2026-01-02 03:04:05', $blob->getCreationDate()->format('Y-m-d H:i:s'));
    }

    public function testHydrateLeavesClientIdNullWhenPatientUuidMissing(): void
    {
        $blob = $this->hydrate([
            'id' => 'result-2',
            'asset_id' => 3,
            'assignmentitem_id' => null,
            'answers' => null,
            'journal_entry' => null,
            'patient_uuid' => null,        // empty -> setClientId never called
            'creation_date' => '2026-07-08 09:10:11.000000',
        ]);

        $this->assertSame('result-2', $blob->getId());
        $this->assertSame(3, $blob->getAssetId());
        $this->assertNull($blob->getAssignmentItemId(), 'LEFT JOIN miss => null assignment item id');
        $this->assertNull($blob->getClientId(), 'empty patient_uuid leaves clientId at its null default');
    }

    public function testHydrateCastsAssetIdToInteger(): void
    {
        $blob = $this->hydrate([
            'id' => 'result-3',
            'asset_id' => '0007',
            'assignmentitem_id' => null,
            'answers' => null,
            'journal_entry' => null,
            'patient_uuid' => null,
            'creation_date' => '2026-01-01 00:00:00.000000',
        ]);

        $this->assertSame(7, $blob->getAssetId());
    }
}
