<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TagRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use Psr\Http\Message\ResponseInterface;

class TagRestController implements IRestController
{
    public function list(ServerRestRequest $request): ResponseInterface
    {
        $tagRepository = new TagRepository();
        try {
            $tags = $tagRepository->listTags() ?? [];
            return RestUtils::returnSingleObjectResponse($tags);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
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
