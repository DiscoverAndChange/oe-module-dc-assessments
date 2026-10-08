<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\ClientSearchQueryDTO;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for ClientSearchQueryDTO request
 * population and emptiness logic.
 *
 * NOTE: the task brief referenced an `isValid()` method; the class actually
 * exposes `isEmpty()`, so these tests characterize `isEmpty()`.
 */
class ClientSearchQueryDTOTest extends TestCase
{
    public function testPopulateFromRequestFullHappyPath(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest([
            'id' => 'c-1',
            'email' => 'jane@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'exactMatch' => 'true',
        ]);

        $this->assertSame('c-1', $dto->id);
        $this->assertSame('jane@example.com', $dto->email);
        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertTrue($dto->exactMatch);
    }

    public function testPopulateFromRequestDefaultsToNullAndFalse(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest([]);

        $this->assertNull($dto->id);
        $this->assertNull($dto->email);
        $this->assertNull($dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertFalse($dto->exactMatch, 'absent exactMatch => false');
    }

    public function testPopulateFromRequestUrlDecodesEmail(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest(['email' => 'jane%40example.com']);

        $this->assertSame('jane@example.com', $dto->email);
    }

    public function testExactMatchOnlyTrueForLiteralStringTrue(): void
    {
        $dto = new ClientSearchQueryDTO();

        $dto->populateFromRequest(['exactMatch' => 'true']);
        $this->assertTrue($dto->exactMatch);

        $dto->populateFromRequest(['exactMatch' => '1']);
        $this->assertFalse($dto->exactMatch, "'1' is not the literal 'true'");

        $dto->populateFromRequest(['exactMatch' => true]);
        $this->assertFalse($dto->exactMatch, 'boolean true !== string "true"');

        $dto->populateFromRequest(['exactMatch' => 'false']);
        $this->assertFalse($dto->exactMatch);
    }

    public function testIsEmptyTrueWhenNothingProvided(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest([]);
        $this->assertTrue($dto->isEmpty());
    }

    public function testIsEmptyFalseWhenIdPresent(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest(['id' => 'c-1']);
        $this->assertFalse($dto->isEmpty());
    }

    public function testIsEmptyFalseWhenEmailPresent(): void
    {
        $dto = new ClientSearchQueryDTO();
        $dto->populateFromRequest(['email' => 'jane@example.com']);
        $this->assertFalse($dto->isEmpty());
    }

    public function testIsEmptyRequiresBothFirstAndLastName(): void
    {
        $dto = new ClientSearchQueryDTO();

        $dto->populateFromRequest(['firstName' => 'Jane']);
        $this->assertTrue($dto->isEmpty(), 'firstName alone is still empty');

        $dto->populateFromRequest(['lastName' => 'Doe']);
        $this->assertTrue($dto->isEmpty(), 'lastName alone is still empty');

        $dto->populateFromRequest(['firstName' => 'Jane', 'lastName' => 'Doe']);
        $this->assertFalse($dto->isEmpty(), 'both names => not empty');
    }
}
