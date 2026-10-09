<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Psr\Http\Message\ResponseInterface;

class EmptyRestController implements IRestController
{
    public function list(ServerRestRequest $request): ResponseInterface
    {
        return ServiceContainer::getResponseFactory()->createResponse(200)->withBody(ServiceContainer::getStreamFactory()->createStream((string) json_encode([])));
    }

    /**
     * @param string $id
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        return ServiceContainer::getResponseFactory()->createResponse(200)->withBody(ServiceContainer::getStreamFactory()->createStream((string) json_encode([])));
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        return ServiceContainer::getResponseFactory()->createResponse(200)->withBody(ServiceContainer::getStreamFactory()->createStream((string) json_encode([])));
    }

    /**
     * @param string $id
     */
    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        return ServiceContainer::getResponseFactory()->createResponse(200)->withBody(ServiceContainer::getStreamFactory()->createStream((string) json_encode([])));
    }
}
