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
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRTask;
use OpenEMR\FHIR\R4\FHIRElement\FHIRTaskStatus;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle\FHIRBundleEntry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\RestControllers\RestControllerHelper;
use OpenEMR\Services\FHIR\FhirResourcesService;
use OpenEMR\Services\FHIR\Serialization\FhirPatientSerializer;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\ResponseInterface;

class TaskRestController
{
    const FHIR_RESOURCE_TYPE = 'Task';

    /**
     * @var FhirResourcesService
     */
    private $fhirService;

    public function __construct(private TaskFHIRResourceService $taskResourceService)
    {
        $this->fhirService = new FhirResourcesService();
    }

    /**
     * Handles the response to the API request GET /fhir/Task and returns the FHIRBundle resource
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
     *
     * @deprecated Unused by the shipped patient SPA (see TEST-PLAN.md "SPA route audit",
     *   2026-10-08): the frontend only loads Tasks via search (Task?patient=...), never by
     *   id. Retained for now as a standard FHIR read for external SMART clients; candidate
     *   for removal (route task.one) in a future release.
     * @see HttpRestRouteHandler::dispatch to see how this parsing is done.
     * @param string $id The unique id of the resource to be returned.
     * @param ServerRestRequest $request
     * @return ResponseInterface
     */
    public function one(ServerRestRequest $request, $id): ResponseInterface
    {
        $result = $this->getOne($id, $request->getPatientUUIDString());
        return RestUtils::returnSingleObjectResponse($result);
    }

    /**
     * Queries for FHIR encounter resources using various search parameters.
     * Search parameters include:
     * - _id (euuid)
     * - patient (puuid)
     * - date {gt|lt|ge|le}
     * @param array<mixed> $searchParams
     * @param string|null $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @return \OpenEMR\FHIR\R4\FHIRResource\FHIRBundle|array<string,mixed> FHIR bundle with query results, if found
     */
    public function getAll($searchParams, $puuidBind = null)
    {
        $processingResult = $this->taskResourceService->getAll($searchParams, $puuidBind);
        $bundleEntries = array();
        /** @var list<FHIRTask> $searchResults */
        $searchResults = $processingResult->getData();
        /** @var string $siteAddr */
        $siteAddr = $GLOBALS['site_addr_oath'];
        /** @var string $redirectUrl */
        $redirectUrl = $_SERVER['REDIRECT_URL'] ?? '';
        foreach ($searchResults as $index => $searchResult) {
            $bundleEntry = [
                'fullUrl' =>  $siteAddr . $redirectUrl . '/' . $searchResult->getId(),
                'resource' => $searchResult
            ];
            $fhirBundleEntry = new FHIRBundleEntry($bundleEntry);
            array_push($bundleEntries, $fhirBundleEntry);
        }
        $bundleSearchResult = $this->fhirService->createBundle(self::FHIR_RESOURCE_TYPE, $bundleEntries, false);
        // Return the FHIR bundle itself. Do NOT route it through
        // RestControllerHelper::responseHandler(): on OpenEMR 8.x that returns a
        // Symfony Response, which returnSingleObjectResponse() then json_encodes to
        // just {"headers":...} instead of the bundle. Also, FHIRBundle omits the
        // `entry` key entirely when there are no results, but the SPA expects an
        // array — so normalize the empty case to a plain array with entry: [].
        if (empty($bundleEntries)) {
            /** @var array<string,mixed> $bundleSearchResult */
            $bundleSearchResult = json_decode((string) json_encode($bundleSearchResult), true);
            $bundleSearchResult['entry'] = [];
        }
        return $bundleSearchResult;
    }

    /**
     * Updates an existing FHIR patient resource.  If no Prefer header is specified it returns the representation default.
     *
     * @deprecated Dead by design (see TEST-PLAN.md "SPA route audit", 2026-10-08): the SPA's
     *   TaskService builds a completed Task but the `client.update<Task>` call is commented
     *   out — there is no active PUT /Task/:id. Item completion flows through a
     *   QuestionnaireResponse create instead. Already a graceful "not yet supported" stub
     *   since v0.12.1; candidate for removal (route task.update) in a future release.
     * @param $request ServerRestRequest The http request.
     * @param string $fhirId The FHIR patient resource id (uuid)
     * @returns 200 if the resource is created, 400 if the resource is invalid
     * @return ResponseInterface
     */
    public function update(ServerRestRequest $request, $fhirId)
    {

        $prefer = $request->getHeader('Prefer');
        $returnType = 'representation';
        try {
            if (!empty($prefer)) {
                $returnType = RestUtils::getReturnTypeFromPrefer($prefer[0]);
            }

            $stream = $request->getBody();
            $stream->rewind();
            $decodedTask = $this->decodeRequest($stream->getContents());

            if ($decodedTask->getId()->getValue() !== $fhirId) {
                $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', xlt('The id in the request body does not match the id in the url.'));
                $response = RestUtils::returnSingleObjectResponse($operationOutcome);
                return $response->withStatus(400); // spec compliant we need to fail this
            }
            // TODO: @adunsulag is there a better more consistent mechanism of forcing/passing the bound puuid versus doing a fetch and
            // then checking on update?
            $foundTask = $this->taskResourceService->getOne($fhirId, $request->getPatientUUIDString());
            if ($foundTask->hasData()) {
                // task exists and can be acessed in the patient context.
                // override the patient here to be the one that was found in the task, there isn't any other way to
                // handle the search at this point.
                /** @var list<FHIRTask> $foundTaskData */
                $foundTaskData = $foundTask->getData();
                $decodedTask->setFor($foundTaskData[0]->getFor());
                $result = $this->taskResourceService->update($fhirId, $decodedTask);
            } else {
                // do we want to treat this as a 404, or a 401?
                $result = $foundTask; // not found
            }
            if (!$result->isValid() || !($returnType === 'representation' || $returnType === 'OperationOutcome')) {
                return RestUtils::getFhirCreateResponseForProcessingResult(self::FHIR_RESOURCE_TYPE, $result);
            }
            /** @var list<string> $resultData */
            $resultData = $result->getData();
            if ($returnType == 'representation') {
                $response = $this->one($request, $resultData[0]);
                if ($response->getStatusCode() !== 200) {
                    return $response; // error code
                }
            } else if ($returnType == 'OperationOutcome') {
                $response = RestUtils::getFhirOperationOutcomeSuccessResponse(self::FHIR_RESOURCE_TYPE, $resultData[0]);
            }
            $response = RestUtils::addFhirLocationHeader($response, self::FHIR_RESOURCE_TYPE, $resultData[0]);
            return $response->withStatus(201);
        } catch (\InvalidArgumentException $exception) {
            ServiceContainer::getLogger()->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', xlt('Invalid request body'));
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(400);
        } catch (\Exception $exception) {
            ServiceContainer::getLogger()->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', xlt('Server Error in creating QuestionnaireResponse resource'));
            $response = RestUtils::returnSingleObjectResponse($operationOutcome);
            return $response->withStatus(500);
        }
    }

    /**
     * Queries for a single FHIR encounter resource by FHIR id
     * @param string $fhirId The FHIR encounter resource id (uuid)
     * @param string|null $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @returns 200 if the operation completes successfully
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOne($fhirId, $puuidBind = null)
    {
        $processingResult = $this->taskResourceService->getOne($fhirId, $puuidBind);
        return RestControllerHelper::handleFhirProcessingResult($processingResult, 200);
    }

    private function decodeRequest(string $requestBody): FHIRTask
    {
        /** @var FHIRTask $task */
        $task = RestUtils::hydrateFhirObjectFromJson($requestBody, FHIRTask::class);
        return $task;
    }
}
