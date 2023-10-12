<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\ClientSearchQueryDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientSearchRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTTPResponseUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\PaginatedResultsService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SystemUserRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Services\FacilityService;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SystemUserRestController implements IRestController
{
    public function __construct()
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        // TODO: @adunsulag should patients be able to hit this list?
        $params = [];
        parse_str($request->getUri()->getQuery(), $params);
        $pagination = PaginatedResultsService::getPaginationFromQuery($params);
        $usersRepo = new SystemUserRepository();
        $users = $usersRepo->getUsers();
        return PaginatedResultsService::returnedPaginatedResultsResponse($users, $pagination);
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        $usersRepo = new SystemUserRepository();
        $users = $usersRepo->getUsers();
        foreach ($users as $user) {
            // the fhir id is the same as the openemr username
            if ($user->getId() == $id) {
                return RestUtils::returnSingleObjectResponse($user);
            }
        }
        return RestUtils::getNotFoundResponse();
    }

    public function create(ServerRestRequest $httpRestRequest): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }

    public function update(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }
}
