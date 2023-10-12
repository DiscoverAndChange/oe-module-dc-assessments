<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TokenRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use Psr\Http\Message\ResponseInterface;

class TokenRestController implements IRestController
{
    public function list(ServerRestRequest $request): ResponseInterface
    {
        $repo = new TokenRepository();
        // we don't deal with shared company tokens right now
        // TODO: @adunsulag if we implement shared tokens or anything like that we can handle that here...
        $result = $repo->getUserTokens($request->getUserId());
        // no objects to serialize here.
        return RestUtils::returnSingleObjectResponse($result);
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
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
