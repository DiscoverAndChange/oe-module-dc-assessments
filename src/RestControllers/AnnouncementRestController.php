<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AnnouncementRestController implements IRestController
{
    public function __construct()
    {
    }

    public function list(ServerRestRequest $httpRestRequest): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        // for now have it be empty
        return $psrFactory->createResponse(200)->withBody(json_encode([]));
    }

    public function one(ServerRestRequest $httpRestRequest, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(200)->withBody(json_encode([]));
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
