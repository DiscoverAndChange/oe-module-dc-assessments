<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobResultDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTMLSanitizer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\LibraryAssetBlobValidator;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\LibraryAssetResultBlobValidator;
use OpenEMR\Services\PatientService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\ResponseInterface;

class LibraryAssetResultRestController implements IRestController
{
    public function __construct(private SystemLogger $logger, private CryptoGen $cryptoGen, private AssignmentCompleter $completer)
    {
    }

    public function list(ServerRestRequest $request): ResponseInterface
    {
        try {
            return RestUtils::returnSingleObjectResponse([]);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    /**
     * @param string $id
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        try {
            if (!$id) {
                throw new \InvalidArgumentException("Missing result id");
            }
            $repo = new LibraryAssetResultBlobRepository($this->logger, $this->cryptoGen);
            $patientRepo = new PatientService();
            if ($request->getAuthRole() == Role::Client) {
                $patientResult = $patientRepo->getOne($request->getPatientUUIDString());
                if (!$patientResult->hasData()) {
                    throw new \RuntimeException("Patient not found for uuid " . $request->getPatientUUIDString());
                }
                $patientData = $patientResult->getData();
                /** @var list<array<string, mixed>> $patientData */
                $patient = $patientData[0];
                // make sure we only grab an asset for the current client
                $result = $repo->getDecryptedAssetResultBlob($id, $patient['pid']);
            } else if (AclMain::aclCheckCore("encounters", "notes")) {
                $result = $repo->getDecryptedAssetResultBlob($id);
            } else {
                throw new AccessDeniedException("encounters", "notes", "User missing encounter/forms ACL to access this result");
            }
            if ($result === null) {
                return RestUtils::getNotFoundResponse();
            }
            $asset = $this->getAsset($result->getAssetId());
            $resultResponse = array_merge($result->jsonSerialize(), [
                'asset' => $asset->jsonSerialize()
                // we used to send client down, not sure its needed and going to skip it for now
            ]);
            return RestUtils::returnSingleObjectResponse($resultResponse);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    /**
     * @deprecated Unused by the shipped patient SPA (see TEST-PLAN.md "SPA route audit",
     *   2026-10-08): AssetService::saveAssetResult() now POSTs a FHIR QuestionnaireResponse
     *   instead of calling this route. Candidate for removal (route
     *   library-asset-results.create) in a future release.
     */
    public function create(ServerRestRequest $request): ResponseInterface
    {
        /** @var array{asset?: array<string, mixed>, ...} $data */
        $data = $request->getBodyAsJson();
        $validator = new LibraryAssetResultBlobValidator();
        $validation = $validator->validate($data, LibraryAssetResultBlobValidator::DATABASE_INSERT_CONTEXT);
        $patientService = new PatientService();
        try {
            return QueryUtils::inTransaction(function () use ($request, $data, $validation, $patientService) {
                if (!$validation->isValid()) {
                    $this->logger->error("Validation failed", ['errors' => $validation->getValidationMessages()]);
                    throw new \InvalidArgumentException("One or more fields was invalid", ErrorCode::VALIDATION_FAILED);
                }

                $client = $this->validateCreateAccessAndReturnClient($request->getUserId(), $request->getPatientUUIDString(), ($data['clientId'] ?? null), $patientService);

                $assignmentRepo = new AssignmentRepository();
                $item = $assignmentRepo->getAssignmentItem($data['assignmentItemId'], UuidRegistry::uuidToString($client['uuid']));
                if ($item === null) {
                    throw new \InvalidArgumentException("Assignment item not found", ErrorCode::INVALID_REQUEST);
                }
                $libraryAssetResultRepo = new LibraryAssetResultBlobRepository($this->logger, $this->cryptoGen);
                $asset = $this->getAsset($data['asset']['id']);
                $dto = new LibraryAssetBlobResultDTO();
                $dto->fromDTO($data);
                $createdResult = $libraryAssetResultRepo->saveLibraryAssetResultBlob(
                    $dto,
                    $asset,
                    $client['uuid'],
                    $request->getUserId(),
                    $request->getPatientUUIDString()
                );

                $item->setResultId($createdResult->getId());

                // TODO: @adunsulag if we ever allow outside embedding into another webpage again we could lazy create the assignment item here
                $this->completer->markAssignmentComplete($item, $client);
                $resultResponse = array_merge($createdResult->jsonSerialize(), [
                    'asset' => $asset->jsonSerialize()
                ]);

                return RestUtils::returnSingleObjectResponse($resultResponse);
            });
        } catch (AccessDeniedException $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return RestUtils::getAccessDeniedResponse($exception);
        } catch (\Exception $e) {
            return RestUtils::getErrorResponse($this->logger, $e);
        }
    }

    /**
     * @param string $id
     */
    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement one() method.
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(400)->withBody($psrFactory->createStream((string) json_encode([])));
    }

    /**
     * @return array<mixed>
     */
    private function validateCreateAccessAndReturnClient(?int $userId, string $patientUuidString, ?string $clientId, PatientService $patientService)
    {

        // first we check to see if we are working as a patient
        if ($patientUuidString === '' && !AclMain::aclCheckCore('encounters', 'notes', (string) $userId)) {
            throw new AccessDeniedException("encounters", "notes", "You do not have permission to create this result");
        } else if ($patientUuidString !== '') {
            // need to grab the patient pid from the uuid
            $result = $patientService->getOne($patientUuidString);
            if (!$result->hasData()) {
                throw new \InvalidArgumentException("Patient uuid in request does not exist", ErrorCode::SYSTEM_ERROR);
            } else {
                $resultData = $result->getData();
                /** @var list<array<string, mixed>> $resultData */
                $client = $resultData[0];
            }
        } else if ($clientId === null || $clientId === '') { // if we are a user and creating results we need a valid client_id
            throw new \InvalidArgumentException("clientId is required", ErrorCode::VALIDATION_FAILED);
        } else {
            /** @var list<array<string, mixed>>|null $client */
            $client = ProcessingResult::extractDataArray($patientService->getOne($clientId));
            if ($client === null || $client === []) {
                throw new \InvalidArgumentException("Invalid client_id in request", ErrorCode::VALIDATION_FAILED);
            } else {
                $client = $client[0];
            }
        }
        return $client;
    }

    /**
     * @return LibraryAssetBlobDTO
     */
    private function getAsset(?int $id)
    {
        $libraryAssetsRepo = new LibraryAssetBlobRepository($this->logger);
        $asset = $libraryAssetsRepo->getAsset((int) $id);
        if ($asset === null) {
            throw new \InvalidArgumentException("Could not find library asset for response", ErrorCode::INVALID_REQUEST);
        }
        return $asset;
    }
}
