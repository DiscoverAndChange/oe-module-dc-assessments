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

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Http\HttpRestRouteHandler;
use OpenEMR\Common\Logging\SystemLogger;
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
use OpenEMR\RestControllers\RestControllerHelper;
use OpenEMR\Services\FHIR\FhirResourcesService;
use OpenEMR\Services\FHIR\UtilsService;
use PHPUnit\Framework\InvalidArgumentException;
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

    public function __construct(QuestionnaireResponseFHIRResourceService $resourceService = null)
    {
        $this->resourceService = $resourceService;
        $this->fhirService = new FhirResourcesService();
    }


    /**
     * Handles the response to the API request GET /fhir/Questionnaire and returns the FHIRBundle resource
     * that was found for the given request.  Any query search parameters are processed by this method.  If the method
     * is run in the patient context (as a logged in patient) it restricts the search to just that patient.
     * @param ServerRestRequest
     * @return FHIRBundle
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
     * @param $id The unique id of the resource to be returned.
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
            if (!empty($prefer)) {
                $returnType = RestUtils::getReturnTypeFromPrefer($prefer[0]);
            }
            $stream = $request->getBody();
            $stream->rewind();
            $decodedQuestionnaire = $this->decodeRequest($stream->getContents());

            $result = $this->resourceService->insert($decodedQuestionnaire);
            if (!$result->isValid() || !($returnType === 'representation' || $returnType === 'OperationOutcome')) {
                return RestUtils::getFhirCreateResponseForProcessingResult('QuestionnaireResponse', $result);
            } else if ($returnType == 'representation') {
                $response = $this->one($request, $result->getData()[0]);
                if ($response->getStatusCode() !== 200) {
                    return $response; // error code
                }
            } else if ($returnType == 'OperationOutcome') {
                $response = RestUtils::getFhirOperationOutcomeSuccessResponse('QuestionnaireResponse', $result->getData()[0]);
            }
            $response = RestUtils::addFhirLocationHeader($response, 'QuestionnaireResponse', $result->getData()[0]);
            return $response->withStatus(201);
        } catch (\InvalidArgumentException $exception) {
            (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'value', $exception->getMessage());
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(400);
        } catch (\Exception $exception) {
            (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', xlt('Server Error in creating QuestionnaireResponse resource'));
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(500);
        }
    }

    public function update(ServerRestRequest $request, $id): ResponseInterface
    {
        // TODO: Implement update() method.
    }

    /**
     * Queries for FHIR encounter resources using various search parameters.
     * Search parameters include:
     * - _id (euuid)
     * - patient (puuid)
     * - date {gt|lt|ge|le}
     * @param $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @return FHIR bundle with query results, if found
     */
    private function getAll($searchParams, $puuidBind = null)
    {
        $processingResult = $this->resourceService->getAll($searchParams, $puuidBind);
        $bundleEntries = array();
        foreach ($processingResult->getData() as $index => $searchResult) {
            $bundleEntry = [
                'fullUrl' =>  $GLOBALS['site_addr_oath'] . ($_SERVER['REDIRECT_URL'] ?? '') . '/' . $searchResult->getId(),
                'resource' => $searchResult
            ];
            $fhirBundleEntry = new FHIRBundleEntry($bundleEntry);
            array_push($bundleEntries, $fhirBundleEntry);
        }
        $bundleSearchResult = $this->fhirService->createBundle('Questionnaire', $bundleEntries, false);
        return $bundleSearchResult;
    }

    /**
     * Queries for a single FHIR encounter resource by FHIR id
     * @param $fhirId The FHIR encounter resource id (uuid)
     * @param $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @returns 200 if the operation completes successfully
     */
    private function getOne($fhirId, $puuidBind = null)
    {
        $processingResult = $this->resourceService->getOne($fhirId, $puuidBind);
        return RestControllerHelper::handleFhirProcessingResult($processingResult, 200);
    }

    private function decodeRequest(string $requestBody)
    {
        return RestUtils::hydrateFhirObjectFromJson($requestBody, FHIRQuestionnaireResponse::class);
    }
}
