<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Database\QueryPagination;
use OpenEMR\Validators\ProcessingResult;

class PaginatedResultsService
{
    public static function getPaginationFromQuery($queryParams): QueryPagination
    {
        $limit = intval($queryParams['_limit'] ?? 50);
        $offset = intval($queryParams['_offset'] ?? 0);
        $pagination = new QueryPagination($limit, $offset);
        return $pagination;
    }
    public static function returnPaginatedResultsForProcessingResponse(ProcessingResult $result)
    {
        $pagination = $result->getPagination();
        $data = [
            "hasMoreData" => $pagination->hasMoreData(),
            "_offset" => $pagination->getNextOffsetId(),
            "_limit" => $pagination->getLimit(),
            "totalCount" => $pagination->getTotalCount(),
            "results" => $result->getData()
        ];

        // for now have it be empty
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($data)));
    }
    public static function returnedPaginatedResultsResponse(array $results, QueryPagination $pagination)
    {
        $psrFactory = new Psr17Factory();

        $returnData = $results;
        $hasMoreData = false;
        $moreResultsLimit = $pagination->getLimit() + 1;
        $resultsCount = count($results);
        $offset = $pagination->getCurrentOffsetId() + $resultsCount;
        $cursor = null;
        (new SystemLogger())->debug("returnPaginatedResultsResponse() inside", ["pagination" => $pagination->jsonSerialize(), "resultCount" => count($results)]);

        if ($resultsCount >= $moreResultsLimit) {
            $returnData = array_slice($results, 0, $pagination->getLimit());
            $offset = $pagination->getCurrentOffsetId() + $pagination->getLimit();
            $hasMoreData = true;
        }

        $data = [
            "hasMoreData" => $hasMoreData,
            "_offset" => $offset,
            "_limit" => $pagination->getLimit(),
            "results" => $returnData
        ];

        // for now have it be empty
        return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($data)));
    }
}
