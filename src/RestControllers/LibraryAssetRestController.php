<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTMLSanitizer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\LibraryAssetBlobValidator;
use Psr\Http\Message\ResponseInterface;

class LibraryAssetRestController implements IRestController
{
    public function __construct(private SystemLogger $logger)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        try {
            $query = $request->getQueryParams();
            $tag = trim($query['tag'] ?? '');
            $psrFactory = new Psr17Factory();
            $libraryAssetsRepo = new LibraryAssetBlobRepository($this->logger);
            $assets = $libraryAssetsRepo->listAssets($tag);
            return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($assets)));
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        try {
            $libraryAssetsRepo = new LibraryAssetBlobRepository($this->logger);
            $asset = $libraryAssetsRepo->getAsset($id);
            if (empty($asset)) {
                return RestUtils::getNotFoundResponse();
            }
            return RestUtils::returnSingleObjectResponse($asset);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        $data = $request->getBodyAsJson();
        $validator = new LibraryAssetBlobValidator();
        $validation = $validator->validate($data, LibraryAssetBlobValidator::DATABASE_INSERT_CONTEXT);
        $transactionCommitted = false;
        try {
            QueryUtils::startTransaction();
            if (!AclMain::aclCheckCore('admin', 'forms')) {
                throw new AccessDeniedException("admin", "forms", "You do not have permission to create library assets");
            }
            if (!$validation->isValid()) {
                $this->logger->errorLogCaller("Validation failed", ['errors' => $validation->getValidationMessages()]);
                throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
            }

            $asset = new LibraryAssetBlobDTO();
            $asset->fromDTO($data);

            $sanitizer = new HTMLSanitizer();
            $asset->setContent($sanitizer->sanitize($asset->getContent()));
            $asset->setDescription($sanitizer->sanitize($asset->getDescription()));
            $asset->setTitle($sanitizer->sanitize($asset->getTitle()));

            $libraryAssetsRepo = new LibraryAssetBlobRepository($this->logger);
            $createdAsset = $libraryAssetsRepo->saveLibraryAssetBlob($asset, $request->getUserId());
            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse($createdAsset);
        } catch (AccessDeniedException $exception) {
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $e) {
                    $this->logger->errorLogCaller("Failed to rollback transaction", ['trace' => $e->getTraceAsString()]);
                }
            }
        }
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody(json_encode([]));
    }
}
