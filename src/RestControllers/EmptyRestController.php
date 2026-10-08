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
        return $psr17->createResponse(200)->withBody($psr17->createStream((string) json_encode([])));
    }

    /**
     * @param string $id
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        return $psr17->createResponse(200)->withBody($psr17->createStream((string) json_encode([])));
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        return $psr17->createResponse(200)->withBody($psr17->createStream((string) json_encode([])));
    }

    /**
     * @param string $id
     */
    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        return $psr17->createResponse(200)->withBody($psr17->createStream((string) json_encode([])));
    }
}
