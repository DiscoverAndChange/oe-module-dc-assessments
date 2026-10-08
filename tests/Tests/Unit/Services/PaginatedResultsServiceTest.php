<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Services;

use OpenEMR\Common\Database\QueryPagination;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\PaginatedResultsService;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Pure-unit (no DB) characterization tests for PaginatedResultsService.
 *
 * Covers QueryPagination construction from query params, the ProcessingResult
 * passthrough response, and the cursor-style "limit + 1" more-data detection in
 * returnedPaginatedResultsResponse(). All responses are built in-memory via the
 * Nyholm Psr17Factory; no DB/network is touched (a SystemLogger debug call is
 * the only side effect).
 */
class PaginatedResultsServiceTest extends TestCase
{
    /** @return array<string,mixed> */
    private function decodeBody(ResponseInterface $response): array
    {
        /** @var array<string,mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);
        return $decoded;
    }

    public function testGetPaginationFromQueryUsesDefaultsWhenParamsAbsent(): void
    {
        $pagination = PaginatedResultsService::getPaginationFromQuery([]);

        $this->assertInstanceOf(QueryPagination::class, $pagination);
        $this->assertSame(50, $pagination->getLimit());
        $this->assertSame(0, $pagination->getCurrentOffsetId());
    }

    public function testGetPaginationFromQueryReadsLimitAndOffset(): void
    {
        $pagination = PaginatedResultsService::getPaginationFromQuery(['_limit' => 10, '_offset' => 20]);

        $this->assertSame(10, $pagination->getLimit());
        $this->assertSame(20, $pagination->getCurrentOffsetId());
        $this->assertSame(30, $pagination->getNextOffsetId(), 'next offset = offset + limit');
    }

    public function testGetPaginationFromQueryCapsLimitAtMax(): void
    {
        $pagination = PaginatedResultsService::getPaginationFromQuery(['_limit' => 5000]);

        $this->assertSame(QueryPagination::MAX_LIMIT, $pagination->getLimit());
    }

    public function testReturnPaginatedResultsForProcessingResponseReflectsPaginationState(): void
    {
        $result = new ProcessingResult();
        $result->setPagination(new QueryPagination(10, 5));
        $result->setData([['a' => 1]]);

        $response = PaginatedResultsService::returnPaginatedResultsForProcessingResponse($result);
        $body = $this->decodeBody($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($body['hasMoreData']);
        $this->assertSame(15, $body['_offset'], 'next offset = 5 + 10');
        $this->assertSame(10, $body['_limit']);
        $this->assertSame(0, $body['totalCount']);
        $this->assertSame([['a' => 1]], $body['results']);
    }

    public function testReturnedPaginatedResultsResponseWithoutMoreData(): void
    {
        $pagination = new QueryPagination(3, 0);
        $response = PaginatedResultsService::returnedPaginatedResultsResponse([1, 2], $pagination);
        $body = $this->decodeBody($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($body['hasMoreData']);
        $this->assertSame(3, $body['_limit']);
        $this->assertSame(2, $body['_offset'], 'offset advances by the full result count');
        $this->assertSame([1, 2], $body['results']);
    }

    public function testReturnedPaginatedResultsResponseTrimsExtraRowAndFlagsMoreData(): void
    {
        // limit 2, but 3 rows handed in (the "+1" probe row) => more data, trimmed to 2.
        $pagination = new QueryPagination(2, 10);
        $response = PaginatedResultsService::returnedPaginatedResultsResponse([1, 2, 3], $pagination);
        $body = $this->decodeBody($response);

        $this->assertTrue($body['hasMoreData']);
        $this->assertSame(2, $body['_limit']);
        $this->assertSame([1, 2], $body['results'], 'probe row is dropped');
        $this->assertSame(12, $body['_offset'], 'offset advances by the limit, not the raw count');
    }

    public function testReturnedPaginatedResultsResponseWithEmptyResults(): void
    {
        $pagination = new QueryPagination(5, 0);
        $response = PaginatedResultsService::returnedPaginatedResultsResponse([], $pagination);
        $body = $this->decodeBody($response);

        $this->assertFalse($body['hasMoreData']);
        $this->assertSame(0, $body['_offset']);
        $this->assertSame([], $body['results']);
    }
}
