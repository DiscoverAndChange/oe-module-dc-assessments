<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\UserRepository;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\MessageTemplateRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\UserService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

class MessageTemplateRestController implements IRestController
{
    public function __construct(private SystemLogger $logger, private Environment $twig, private GlobalConfig $config)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        try {
            $query = $request->getQueryParams();
            $puuid = $query['clientId'] ?? '';
            $patientRepo = new PatientService();
            $patient = ProcessingResult::extractDataArray($patientRepo->getOne($puuid));
            if (empty($patient)) {
                return RestUtils::getNotFoundResponse();
            } else {
                $patient = $patient[0];
            }
            $facRepo = new FacilityService();
            $primaryEntity = $facRepo->getPrimaryBusinessEntity();
            $userId = $request->getUserId();
            $userRepo = new UserService();
            $user = $userRepo->getUser($userId);
            if (empty($user)) {
                throw new \InvalidArgumentException("User not found for request");
            }

            $messageTemplateRepo = new MessageTemplateRepository($this->twig, $this->config);
            $result = $messageTemplateRepo->getTemplateForClient($patient, $primaryEntity);

            $result = array_merge($result, [
                'from' => $this->config->getNotificationDefaultFrom()
                ,'to' => $patient['email'] ?? ''
                ,'replyTo' => $this->config->getNotificationDefaultReplyTo()
                ,'testEmail' => $user['email'] ?? ''
            ]);
            return RestUtils::returnSingleObjectResponse($result);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($e);
        }
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        try {
            return RestUtils::returnSingleObjectResponse([]);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
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
