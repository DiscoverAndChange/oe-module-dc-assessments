<?php

/**
 * FHIR Resource Controller example for handling and responding to
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2022 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Http\HttpRestRouteHandler;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle\FHIRBundleEntry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\IRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Services\FHIR\FhirResourcesService;
use OpenEMR\Services\FHIR\UtilsService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class QuestionnaireResponseRestController implements IRestController
{
    /**
     * @var QuestionnaireResponseFHIRResourceService
     */
    private $resourceService;

    /**
     * @var FhirResourcesService
     */
    private $fhirService;

    public function __construct(QuestionnaireResponseFHIRResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
        $this->fhirService = new FhirResourcesService();
    }


    /**
     * Handles the response to the API request GET /fhir/Questionnaire and returns the FHIRBundle resource
     * that was found for the given request.  Any query search parameters are processed by this method.  If the method
     * is run in the patient context (as a logged in patient) it restricts the search to just that patient.
     * @param ServerRestRequest $request
     * @return ResponseInterface
     */
    public function list(ServerRestRequest $request): ResponseInterface
    {
        if ($request->isPatientRequest()) {
            // only allow access to data of binded patient
            $result = $this->getAll($request->getQueryParams(), $request->getPatientUUIDString());
        } else {
            /**
             * If you need to check the API against any kind of ACL the RestConfig object will do an authorization check
             * and handle the API result back to the HTTP client
             */
            // RestConfig::authorization_check("patients", "med");
            $result = $this->getAll($request->getQueryParams());
        }
        return RestUtils::returnSingleObjectResponse($result);
    }

    /**
     * Retrieves a single api resource.  Handles the response to the API request GET /fhir/Questionnaire/:fhirId
     * The $fhirId is populated from the API request by the rest route dispatcher.
     * @see HttpRestRouteHandler::dispatch to see how this parsing is done.
     * @param string $id The unique id of the resource to be returned.
     * @param ServerRestRequest $request
     * @return ResponseInterface
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        $processingResult = $this->resourceService->getOne($id, $request->getPatientUUIDString());
        return RestUtils::getResponseForProcessingResult($processingResult);
    }

    public function create(ServerRestRequest $request): ResponseInterface
    {
        // TODO: @adunsulag need to catch exceptions here...

        // TODO: @adunsulag is there a way to abstract this so we can make it generic per resource?
        // note the return type for the prefer is based on this specification: https://build.fhir.org/http.html#return
        $prefer = $request->getHeader('Prefer');
        $returnType = 'representation';
        try {
            if ($prefer !== []) {
                $returnType = RestUtils::getReturnTypeFromPrefer($prefer[0]);
            }
            $stream = $request->getBody();
            $stream->rewind();
            /** @var FHIRQuestionnaireResponse $decodedQuestionnaire */
            $decodedQuestionnaire = $this->decodeRequest($stream->getContents());

            $result = $this->resourceService->insert($decodedQuestionnaire);
            /** @var array<int, mixed> $resultData */
            $resultData = $result->getData();
            if (!$result->isValid() || !($returnType === 'representation' || $returnType === 'OperationOutcome')) {
                return RestUtils::getFhirCreateResponseForProcessingResult('QuestionnaireResponse', $result);
            }
            // only 'representation' and 'OperationOutcome' remain here (guarded above)
            /** @var string $createdId */
            $createdId = $resultData[0];
            if ($returnType === 'representation') {
                $response = $this->one($request, $createdId);
                if ($response->getStatusCode() !== 200) {
                    return $response; // error code
                }
            } else {
                $response = RestUtils::getFhirOperationOutcomeSuccessResponse('QuestionnaireResponse', $createdId);
            }
            $response = RestUtils::addFhirLocationHeader($response, 'QuestionnaireResponse', $createdId);
            return $response->withStatus(201);
        } catch (\InvalidArgumentException $exception) {
            ServiceContainer::getLogger()->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'value', $exception->getMessage());
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(400);
        } catch (\Exception $exception) {
            ServiceContainer::getLogger()->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', xlt('Server Error in creating QuestionnaireResponse resource'));
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(500);
        }
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement update() method.
        return RestUtils::getNotFoundResponse();
    }

    /**
     * Queries for FHIR encounter resources using various search parameters.
     * Search parameters include:
     * - _id (euuid)
     * - patient (puuid)
     * - date {gt|lt|ge|le}
     * @param array<mixed> $searchParams
     * @param string|null $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @return \OpenEMR\FHIR\R4\FHIRResource\FHIRBundle|array<string, mixed> FHIR bundle with query results, if found
     */
    private function getAll($searchParams, $puuidBind = null)
    {
        $processingResult = $this->resourceService->getAll($searchParams, $puuidBind);
        $bundleEntries = array();
        /** @var array<int, \OpenEMR\FHIR\R4\FHIRResource\FHIRDomainResource> $resultData */
        $resultData = $processingResult->getData();
        /** @var string $siteAddr */
        $siteAddr = $GLOBALS['site_addr_oath'];
        /** @var string $redirectUrl */
        $redirectUrl = $_SERVER['REDIRECT_URL'] ?? '';
        foreach ($resultData as $index => $searchResult) {
            $bundleEntry = [
                'fullUrl' =>  $siteAddr . $redirectUrl . '/' . $searchResult->getId(),
                'resource' => $searchResult
            ];
            $fhirBundleEntry = new FHIRBundleEntry($bundleEntry);
            array_push($bundleEntries, $fhirBundleEntry);
        }
        /** @var FHIRBundle $bundleSearchResult */
        $bundleSearchResult = $this->fhirService->createBundle('Questionnaire', $bundleEntries, false);
        // FHIRBundle omits the `entry` key when empty, but the SPA expects an
        // array; normalize the empty case to a plain array with entry: [].
        if ($bundleEntries === []) {
            /** @var array<string, mixed> $bundleSearchResult */
            $bundleSearchResult = json_decode((string) json_encode($bundleSearchResult), true);
            $bundleSearchResult['entry'] = [];
        }
        return $bundleSearchResult;
    }

    /**
     * @return object
     */
    private function decodeRequest(string $requestBody)
    {
        return RestUtils::hydrateFhirObjectFromJson($requestBody, FHIRQuestionnaireResponse::class);
    }
}
