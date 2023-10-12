<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface IRestController
{
    public function list(ServerRestRequest $request): ResponseInterface;
    public function one(ServerRestRequest $request, $id): ResponseInterface;

    public function create(ServerRestRequest $request): ResponseInterface;
    public function update(ServerRestRequest $request, $id): ResponseInterface;
}
