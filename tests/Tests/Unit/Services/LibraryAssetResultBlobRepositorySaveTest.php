<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use OpenEMR\Services\Search\SearchModifier;
use OpenEMR\Services\Search\StringSearchField;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed write/read tests for LibraryAssetResultBlobRepository against the oe-test-db
 * harness. These complement (do NOT duplicate) LibraryAssetResultBlobRepositoryTest, which
 * covers the pure hydrator and the getDecryptedAssetResultBlob() LEFT JOIN read.
 *
 * Covered here (phpstan-flagged service-layer methods, no AclMain / no network):
 *   - saveLibraryAssetResultBlob() happy path: INSERT (incl. the client_id subquery that
 *     resolves patient_data.uuid -> pid) then read the row back via
 *     getDecryptedAssetResultBlob().
 *   - search() by exact id through FhirSearchWhereClauseBuilder.
 *   - saveTags() linking only the tags that exist in dac_Tag.
 *
 * shouldEncrypt() always returns true, so to keep CryptoGen entirely out of the picture
 * (no encryption keys/globals needed) every blob is seeded with EMPTY answers/journal:
 * with empty answers the encrypt branch is skipped, and with an empty journal the journal
 * encrypt branch is skipped. The read-back hydrator likewise skips decryptStandard().
 *
 * Seeding is 'phptest'-prefixed and removed in tearDown(), children before parents to
 * respect the foreign keys:
 *   dac_LibraryAssetResultBlob.client_id -> patient_data.id (we force pid == id so the
 *   repo's join on client_id = pid lines up with that FK),
 *   dac_LibraryAssetResultBlob.asset_id -> dac_LibraryAssetBlob.id,
 *   dac_LibraryAssetBlob.created_by/last_updated_by -> users(id) (seeded as 1).
 */
class LibraryAssetResultBlobRepositorySaveTest extends TestCase
{
    /** patient_data.id (forced == pid) used as the result blob client_id. */
    private int $pid;

    /** canonical uuid string of the seeded patient. */
    private string $patientUuid;

    /** dac_LibraryAssetBlob.id of the seeded (article-type) asset. */
    private int $assetId;

    protected function setUp(): void
    {
        parent::setUp();

        // --- seed a throwaway patient with pid == id -------------------------------
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO patient_data (fname, lname, pubpid, date) VALUES ('phptest-larbsave', 'phptest-larbsave', 'phptest-larbsave', NOW())"
        );
        $this->pid = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM patient_data WHERE fname = 'phptest-larbsave' ORDER BY id DESC LIMIT 1",
            'id',
            []
        );
        // the repo joins client_id to patient_data.pid, while the FK references patient_data.id
        QueryUtils::sqlStatementThrowException("UPDATE patient_data SET pid = ? WHERE id = ?", [$this->pid, $this->pid]);
        // OpenEMR assigns patient uuids out of band; the bare seed has none.
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        /** @var string $uuidBytes */
        $uuidBytes = QueryUtils::fetchSingleValue("SELECT uuid FROM patient_data WHERE id = ?", 'uuid', [$this->pid]);
        $this->patientUuid = UuidRegistry::uuidToString($uuidBytes);

        // --- seed a throwaway asset (result blob asset_id -> dac_LibraryAssetBlob.id) ---
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO dac_LibraryAssetBlob (title, description, type, view_count, use_count, original_creator, content, created_by, last_updated_by)"
            . " VALUES ('phptest-larbsave-asset', '', 'article', 0, 0, 'phptest', '', 1, 1)"
        );
        $this->assetId = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM dac_LibraryAssetBlob WHERE title = 'phptest-larbsave-asset' ORDER BY id DESC LIMIT 1",
            'id',
            []
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // child rows first.
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM dac_LibraryAssetBlobTag WHERE library_asset_blob_id IN (SELECT id FROM dac_LibraryAssetBlob WHERE title LIKE 'phptest%')",
            [],
            true
        );
        QueryUtils::sqlStatementThrowException("DELETE FROM dac_LibraryAssetResultBlob WHERE id LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM dac_LibraryAssetBlob WHERE title LIKE 'phptest%'", [], true);
        QueryUtils::sqlStatementThrowException("DELETE FROM patient_data WHERE fname LIKE 'phptest%'", [], true);
    }

    private function repo(): LibraryAssetResultBlobRepository
    {
        return new LibraryAssetResultBlobRepository(new SystemLogger(), new CryptoGen());
    }

    // ------------------------------------------------------------------------
    // saveLibraryAssetResultBlob()
    // ------------------------------------------------------------------------

    public function testSaveLibraryAssetResultBlobInsertsAndReadsBack(): void
    {
        $resultBlob = new LibraryAssetBlobResultDTO();
        // keep a deterministic, phptest-prefixed id (the save keeps a non-empty id as-is).
        $resultBlob->setId('phptest-larbsave-result-1');
        $resultBlob->setAnswers([]);   // empty -> encrypt branch skipped, no CryptoGen
        $resultBlob->setJournal(null); // empty -> journal encrypt branch skipped

        $asset = new LibraryAssetBlobDTO();
        $asset->setId($this->assetId); // type defaults to 'article'

        // The repo's client_id subquery matches patient_data.uuid byte-for-byte; pass the
        // raw 16 bytes so it unambiguously resolves to the patient's pid.
        $clientUuidBytes = UuidRegistry::uuidToBytes($this->patientUuid);

        $repo = $this->repo();
        $saved = $repo->saveLibraryAssetResultBlob($resultBlob, $asset, $clientUuidBytes);

        $this->assertInstanceOf(LibraryAssetBlobResultDTO::class, $saved);
        $this->assertSame('phptest-larbsave-result-1', $saved->getId());
        $this->assertSame([], $saved->getAnswers(), 'answers are cleared on the returned DTO');
        $this->assertNull($saved->getJournal(), 'journal is cleared on the returned DTO');

        // read back by id + pid: this only matches if the subquery populated client_id = pid.
        $readBack = $repo->getDecryptedAssetResultBlob('phptest-larbsave-result-1', $this->pid);
        $this->assertInstanceOf(LibraryAssetBlobResultDTO::class, $readBack);
        $this->assertSame('phptest-larbsave-result-1', $readBack->getId());
        $this->assertSame($this->assetId, $readBack->getAssetId());
        $this->assertSame([], $readBack->getAnswers(), 'empty answers hydrate to []');
        $this->assertNull($readBack->getJournal(), 'empty journal stays null');
        // patient_uuid comes from the pd join on client_id = pid, so clientId hydrates to our patient.
        $this->assertSame($this->patientUuid, $readBack->getClientId());
    }

    public function testSaveLibraryAssetResultBlobPersistsCreationDate(): void
    {
        $resultBlob = new LibraryAssetBlobResultDTO();
        $resultBlob->setId('phptest-larbsave-result-date');
        $resultBlob->setAnswers([]);
        $resultBlob->setJournal(null);
        $resultBlob->setCreationDate(new \DateTime('2026-03-04 05:06:07'));

        $asset = new LibraryAssetBlobDTO();
        $asset->setId($this->assetId);

        $repo = $this->repo();
        $repo->saveLibraryAssetResultBlob($resultBlob, $asset, UuidRegistry::uuidToBytes($this->patientUuid));

        $readBack = $repo->getDecryptedAssetResultBlob('phptest-larbsave-result-date', $this->pid);
        $this->assertInstanceOf(LibraryAssetBlobResultDTO::class, $readBack);
        $this->assertInstanceOf(\DateTime::class, $readBack->getCreationDate());
        $this->assertSame('2026-03-04 05:06:07', $readBack->getCreationDate()->format('Y-m-d H:i:s'));
    }

    // ------------------------------------------------------------------------
    // search()
    // ------------------------------------------------------------------------

    public function testSearchReturnsSeededBlobByExactId(): void
    {
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO dac_LibraryAssetResultBlob (id, answers, journal_entry, asset_id, client_id) VALUES (?, NULL, NULL, ?, ?)",
            ['phptest-larbsave-search-1', $this->assetId, $this->pid]
        );

        // StringSearchField EXACT resolves to `BINARY larb.id = ?`, keeping the match
        // scoped to our seeded row regardless of any pre-existing data.
        $field = new StringSearchField('larb.id', 'phptest-larbsave-search-1', SearchModifier::EXACT);
        $result = $this->repo()->search(['larb.id' => $field]);

        $data = $result->getData();
        $this->assertCount(1, $data, 'exactly the one seeded blob matches the id filter');
        $this->assertSame('phptest-larbsave-search-1', $data[0]['id']);
    }

    public function testSearchReturnsEmptyWhenNoRowMatches(): void
    {
        $field = new StringSearchField('larb.id', 'phptest-no-such-result-blob', SearchModifier::EXACT);
        $result = $this->repo()->search(['larb.id' => $field]);

        $this->assertCount(0, $result->getData());
    }

    // ------------------------------------------------------------------------
    // saveTags()
    // ------------------------------------------------------------------------

    public function testSaveTagsLinksOnlyExistingTags(): void
    {
        $asset = new LibraryAssetBlobDTO();
        $asset->setId($this->assetId);
        // 'Anxiety' and 'Depression' are seeded in dac_Tag by table.sql; the third does not exist.
        $asset->setTags(['Anxiety', 'Depression', 'phptest-nonexistent-tag']);

        $returned = $this->repo()->saveTags($asset);

        $tags = $returned->getTags();
        $this->assertIsArray($tags);
        $this->assertContains('Anxiety', $tags);
        $this->assertContains('Depression', $tags);
        $this->assertNotContains('phptest-nonexistent-tag', $tags, 'tags absent from dac_Tag are dropped');
        $this->assertCount(2, $tags);

        // the join rows are actually persisted (only the two resolvable tags).
        $count = (int) QueryUtils::fetchSingleValue(
            "SELECT COUNT(*) AS c FROM dac_LibraryAssetBlobTag WHERE library_asset_blob_id = ?",
            'c',
            [$this->assetId]
        );
        $this->assertSame(2, $count);
    }

    public function testSaveTagsIsIdempotentAndReplacesPriorLinks(): void
    {
        $repo = $this->repo();

        $first = new LibraryAssetBlobDTO();
        $first->setId($this->assetId);
        $first->setTags(['Anxiety', 'Depression']);
        $repo->saveTags($first);

        // re-save with a different (single) tag: saveTags deletes the old links first.
        $second = new LibraryAssetBlobDTO();
        $second->setId($this->assetId);
        $second->setTags(['Anxiety']);
        $repo->saveTags($second);

        $count = (int) QueryUtils::fetchSingleValue(
            "SELECT COUNT(*) AS c FROM dac_LibraryAssetBlobTag WHERE library_asset_blob_id = ?",
            'c',
            [$this->assetId]
        );
        $this->assertSame(1, $count, 'prior links are cleared before the new set is written');
    }
}
