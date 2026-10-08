<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for LibraryAssetBlobResultDTO
 * hydration/serialization. These lock in the current array<->object behavior
 * that an upcoming type-refactor will rewrite.
 */
class LibraryAssetBlobResultDTOTest extends TestCase
{
    public function testConstructorGeneratesIdAndCreationDate(): void
    {
        $dto = new LibraryAssetBlobResultDTO();

        $this->assertIsString($dto->getId());
        $this->assertNotSame('', $dto->getId());
        // generateId() uses a v4 UUID string.
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $dto->getId()
        );
        $this->assertInstanceOf(\DateTime::class, $dto->getCreationDate());
    }

    public function testConstructorDefaultsForOtherFields(): void
    {
        $dto = new LibraryAssetBlobResultDTO();

        $this->assertNull($dto->getAssetId());
        $this->assertSame([], $dto->getAnswers());
        $this->assertNull($dto->getAssignmentItemId());
        $this->assertNull($dto->getJournal());
        $this->assertNull($dto->getClientId());
    }

    public function testGenerateIdProducesDistinctValues(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $first = $dto->getId();
        $dto->generateId();
        $second = $dto->getId();

        $this->assertNotSame($first, $second, 'generateId() mints a fresh UUID');
    }

    public function testSettersAreFluent(): void
    {
        $dto = new LibraryAssetBlobResultDTO();

        $this->assertSame($dto, $dto->setId('abc'));
        $this->assertSame($dto, $dto->setAssetId(7));
        $this->assertSame($dto, $dto->setAnswers(['a' => 1]));
        $this->assertSame($dto, $dto->setAssignmentItemId('item-1'));
        $this->assertSame($dto, $dto->setJournal('note'));
        $this->assertSame($dto, $dto->setClientId('client-1'));
        $this->assertSame($dto, $dto->setCreationDate(new \DateTime()));
    }

    public function testNullableStringSettersAcceptNull(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->setId(null);
        $dto->setAssignmentItemId(null);
        $dto->setJournal(null);
        $dto->setClientId(null);
        $dto->setAssetId(null);

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getAssignmentItemId());
        $this->assertNull($dto->getJournal());
        $this->assertNull($dto->getClientId());
        $this->assertNull($dto->getAssetId());
    }

    public function testJsonSerializeExposesOnlySubsetOfFields(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->setId('id-1');
        $dto->setAnswers(['q1' => 'yes']);
        $dto->setJournal('my journal');
        $dto->setAssetId(42);
        $dto->setAssignmentItemId('item-9');
        $dto->setClientId('client-9');
        $dto->setCreationDate(new \DateTime('2026-05-06T07:08:09+00:00'));

        $out = $dto->jsonSerialize();

        $this->assertSame('id-1', $out['id']);
        $this->assertSame(['q1' => 'yes'], $out['answers']);
        $this->assertSame('my journal', $out['journal']);
        $this->assertSame('2026-05-06T07:08:09+00:00', $out['creationDate']);

        // Characterize: assetId / assignmentItemId / clientId are NOT serialized.
        $this->assertSame(['id', 'answers', 'journal', 'creationDate'], array_keys($out));
        $this->assertArrayNotHasKey('assetId', $out);
        $this->assertArrayNotHasKey('assignmentItemId', $out);
        $this->assertArrayNotHasKey('clientId', $out);
    }

    public function testJsonSerializeFormatsCreationDateAsAtom(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $date = new \DateTime('2026-01-02T03:04:05+00:00');
        $dto->setCreationDate($date);

        $this->assertSame($date->format(DATE_ATOM), $dto->jsonSerialize()['creationDate']);
    }

    public function testFromDtoPopulatesHappyPath(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $creationDate = new \DateTime('2026-03-04T05:06:07+00:00');
        $dto->fromDTO([
            'id' => 'uuid-abc',
            'asset' => ['id' => 55],
            'answers' => ['q1' => 'a', 'q2' => 'b'],
            'assignmentItemId' => 'item-77',
            'journal' => 'journal text',
            'clientId' => 'client-77',
            'creationDate' => $creationDate,
        ]);

        $this->assertSame('uuid-abc', $dto->getId());
        $this->assertSame(55, $dto->getAssetId());
        $this->assertSame(['q1' => 'a', 'q2' => 'b'], $dto->getAnswers());
        $this->assertSame('item-77', $dto->getAssignmentItemId());
        $this->assertSame('journal text', $dto->getJournal());
        $this->assertSame('client-77', $dto->getClientId());
        $this->assertSame($creationDate, $dto->getCreationDate());
    }

    /**
     * REGRESSION (fixed v0.12.3): a creationDate arriving as an ISO-8601 string (the
     * realistic JSON payload) previously TypeErrored against the non-null \DateTime
     * setter. It is now parsed; an invalid/empty string falls back to "now".
     */
    public function testFromDtoParsesStringCreationDate(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->fromDTO(['creationDate' => '2026-03-04T05:06:07+00:00']);

        $this->assertInstanceOf(\DateTime::class, $dto->getCreationDate());
        $this->assertSame('2026-03-04T05:06:07+00:00', $dto->getCreationDate()->format(DATE_ATOM));

        $dtoInvalid = new LibraryAssetBlobResultDTO();
        $dtoInvalid->fromDTO(['creationDate' => 'not-a-date']);
        $this->assertInstanceOf(\DateTime::class, $dtoInvalid->getCreationDate());
    }

    public function testFromDtoDefaultsWhenKeysAbsent(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->fromDTO([]);

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getAssetId());
        $this->assertSame([], $dto->getAnswers());
        $this->assertNull($dto->getAssignmentItemId());
        $this->assertSame('client-not-set', $dto->getClientId() ?? 'client-not-set');
        // Characterize the quirk: an ABSENT journal becomes '' (empty string),
        // not null, because fromDTO() uses ($data['journal'] ?? '').
        $this->assertSame('', $dto->getJournal());
        // Absent creationDate => a freshly minted DateTime.
        $this->assertInstanceOf(\DateTime::class, $dto->getCreationDate());
    }

    public function testFromDtoCastsNonArrayAnswersToArray(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->fromDTO(['answers' => 'scalar']);

        // (array) cast wraps a scalar into a single-element array.
        $this->assertSame(['scalar'], $dto->getAnswers());
    }

    public function testFromDtoReadsNestedAssetId(): void
    {
        $dto = new LibraryAssetBlobResultDTO();
        $dto->fromDTO(['asset' => ['id' => 9]]);
        $this->assertSame(9, $dto->getAssetId());

        $dto2 = new LibraryAssetBlobResultDTO();
        $dto2->fromDTO(['asset' => []]);
        $this->assertNull($dto2->getAssetId(), 'missing asset.id => null');
    }
}
