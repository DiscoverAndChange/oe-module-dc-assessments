<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use OpenEMR\Common\Http\Psr17Factory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Psr\Http\Message\ResponseInterface;

class EmptyRestController implements IRestController
{
    public function list(ServerRestRequest $request): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        return $psr17->createResponse(200)->withBody($psr17->createStream(json_encode([])));
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        // TODO: Implement create() method.
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement update() method.
    }
}
