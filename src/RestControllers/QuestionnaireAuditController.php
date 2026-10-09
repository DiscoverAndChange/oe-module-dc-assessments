<?php

/**
 * Handles the portal admin audit side of things, this may be refactored to be a generic backend view controller...
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers;

use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Forms\FormQuestionnaireAssessment;
use OpenEMR\BC\ServiceContainer;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Pdf\PatientPortalPDFDocumentCreator;
use OpenEMR\Services\EncounterService;
use OpenEMR\Services\FormService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use Twig\Environment;

class QuestionnaireAuditController
{
    public function __construct(private SystemLogger $logger, private Environment $twig, private QuestionnaireService $questionnaireService, private QuestionnaireResponseService $qrService, private AssignmentRepository $assignmentRepository, private GlobalConfig $config, private string $publicPath)
    {
    }

    /**
     * @param string $action
     * @param array<mixed> $queryVars
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch($action, array $queryVars)
    {
        try {
            if ($action == 'view') {
                return $this->actionView($queryVars);
            } else if ($action == 'chart-assignment-to-encounter') {
                return $this->actionChartAssignmentToEncounter($queryVars);
            } else {
                return $this->actionNotFound($action);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return $this->returnError($exception);
        }
    }

    // TODO: Is there a way we can just move this to our standard apis...
    /**
     * @param array<mixed> $queryVars
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function actionChartAssignmentToEncounter($queryVars)
    {
        // action chart questionnaire to encounter
        /**
         * auditRecordId: event.target.dataset.recordId
        ,encounterId: eid
        ,csrfToken: csrfToken
         */
        try {
            // we do this before we start the transaction to avoid autocommits during the service.
            $encounterService = new EncounterService();
            return QueryUtils::inTransaction(function () use ($encounterService) {
                /** @var array<string, mixed> $phpInput */
                $phpInput = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            $auditRecordId = $phpInput['auditRecordId'] ?? null;
            $encounterId = $phpInput['encounterId'] ?? null;
            $csrfToken = $phpInput['csrfToken'] ?? null;
            if (($auditRecordId === null || $auditRecordId === '') || ($encounterId === null || $encounterId === '') || ($csrfToken === null || $csrfToken === '')) {
                throw new \InvalidArgumentException('Missing eid, recordId, or csrfToken', ErrorCode::VALIDATE_DATA_MISSING);
            }
            if (CsrfUtils::verifyCsrfToken($csrfToken) === false) {
                throw new \InvalidArgumentException('Invalid csrfToken', ErrorCode::INVALID_REQUEST);
            }
            // make sure the current user can do this operation
            if (!AclMain::aclCheckCore("encounters", "forms")) {
                throw new AccessDeniedException('encounters', 'forms', "Access Denied.");
            }
            // how do we get the lform data...
            $auditRecord = QueryUtils::fetchRecords("select * from onsite_portal_activity where id = ?", [$auditRecordId]);
            if ($auditRecord === [] || $auditRecord[0]['activity'] !== 'dc-assignment') {
                throw new \InvalidArgumentException('Invalid recordId', ErrorCode::INVALID_REQUEST);
            }

            $assignmentItems = $this->assignmentRepository->getAssignmentItemsForAuditId($auditRecordId);
            if ($assignmentItems === []) {
                throw new \InvalidArgumentException('Invalid recordId', ErrorCode::INVALID_REQUEST);
            }
            $assignmentItem = $assignmentItems[0];

            $result = $encounterService->getEncounterById($encounterId);
            if (!$result->hasData()) {
                throw new \InvalidArgumentException('Invalid encounterId', ErrorCode::INVALID_REQUEST);
            }
            // need to update our onsite access pieces

            // TODO: we would need to handle the audit of each item differently here...
            if (!$assignmentItem instanceof AssignedQuestionnaire) {
                throw new \InvalidArgumentException('Invalid recordId', ErrorCode::INVALID_REQUEST);
            }
            $auditRecord = $auditRecord[0];
            $qr = $this->qrService->fetchQuestionnaireResponseByResponseId($assignmentItem->getResultId());

            $questionnaire = $this->questionnaireService->fetchQuestionnaireById(null, UuidRegistry::uuidToBytes($qr['questionnaire_id']));
//            $qJSON = json_decode($questionnaire['questionnaire'], true, 512, JSON_THROW_ON_ERROR);
            /** @var non-empty-list<array{pid: string}> $encounterData */
            $encounterData = $result->getData();
            $formId = $this->saveEncounterForm($auditRecord, $questionnaire, $qr, $encounterData[0]['pid'], $encounterId);

            // now we need to mark the audit record as locked and saved.
            $this->updateOnSitePortalActivityWithCompletion($auditRecord['id']);

                return RestUtils::returnSingleObjectResponse(['formid' => $formId]);
            });
        } catch (\Exception $exception) {
            return RestUtils::getErrorResponse($this->logger, $exception);
        }
    }

    /**
     * @param array<string, ?string> $auditRecord
     * @param array<mixed> $questionnaire
     * @param array<mixed> $questionnaireResponse
     * @param string $pid
     * @param mixed $encounterId
     * @return mixed
     */
    private function saveEncounterForm($auditRecord, $questionnaire, $questionnaireResponse, $pid, $encounterId)
    {
        // how is the encounter form saved
        $metaData = $this->qrService->extractResponseMetaData($questionnaireResponse, true);
        $formQuestionnaireAssessment = new FormQuestionnaireAssessment();
        $formQuestionnaireAssessment->setEncounter($encounterId);
        $formQuestionnaireAssessment->setPid((int) $pid);
        $formQuestionnaireAssessment->setCopyright($qJSON['copyright'] ?? '');
        $formQuestionnaireAssessment->setFormName(($auditRecord['narrative'] ?? ''));
        $formQuestionnaireAssessment->setResponseMeta($metaData);
        $formQuestionnaireAssessment->setQuestionnaireId($questionnaire['id']);
        $formQuestionnaireAssessment->setQuestionnaire($questionnaire['questionnaire']);
        $formQuestionnaireAssessment->setQuestionnaireResponse($questionnaireResponse['questionnaire_response']);
        $formQuestionnaireAssessment->setResponseId($questionnaireResponse['response_id']);
        $formQuestionnaireAssessment->setLform('');
        $formQuestionnaireAssessment->setLformResponse('');

        $formService = new FormService();
        $savedForm = $formService->saveEncounterForm($formQuestionnaireAssessment);
        return $savedForm->getFormId();
    }

    /**
     * @param array<mixed> $queryVars
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function actionView($queryVars)
    {
        // TODO: check that pid, recordId, and qr are set otherwise throw invalidargumentexception


        if (!isset($queryVars['recordId']) || $queryVars['recordId'] === '') {
            return $this->actionNotFound('view');
        }
        $assignmentItems = $this->assignmentRepository->getAssignmentItemsForAuditId($queryVars['recordId']);
        // for now there should only be one audit to one assignment item
        $assignmentItem = $assignmentItems[0] ?? null;
        // for now we are just handling questionnaires
        if ($assignmentItem === null) {
            return $this->actionNotFound('view');
        }
        $auditId = $queryVars['recordId'];
        $pid = $queryVars['pid'];
        if ($assignmentItem instanceof AssignedQuestionnaire) {
            return $this->displayAuditForAssignedQuestionnaire($auditId, $pid, $assignmentItem);
        } else {
//            return $this->displayPrintVersionForResults();
            return $this->displayAuditForSmartAppAssignment($auditId, $pid, $assignmentItem);
        }
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function displayPrintVersionForResults()
    {
        $data = [
            'scriptPath' => $this->publicPath . "backend"
        ];
        $body = $this->twig->render('discoverandchange/portal/audit/assignment-item-pdf-print.html.twig', $data);
//        $pdfPrinter = new PatientPortalPDFDocumentCreator();
//        $pdfObject = $pdfPrinter->createPdfObjectForHtmlDocument($body);
//        $fileName = uniqid("assignment-item-") . ".pdf";
//            header('Content-type: application/pdf');
//            header("Content-Disposition: attachment; filename=" . $fileName);
//            $pdfObject->Output($fileName, 'D');
//            exit();
        $response = ServiceContainer::getResponseFactory()->createResponse(200, 'OK');
        return $response->withBody(ServiceContainer::getStreamFactory()->createStream($body));
    }
    /**
     * @param mixed $auditId
     * @param mixed $pid
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function displayAuditForSmartAppAssignment($auditId, $pid, Assignment $assignmentItem)
    {
        $category = $this->getCategoryList();
        $encounterService = new EncounterService();
        $patientService = new PatientService();
        $puuid = UuidRegistry::uuidToString($patientService->getUuid($pid));
        $encounters = $encounterService->getEncountersForPatientByPid($pid);

        $response = ServiceContainer::getResponseFactory()->createResponse(200, 'OK');

        $baseUrl = $this->config->getSmartAppAdminRootPath();

        if ($assignmentItem instanceof AssignedAssessment) {
            $smartUrl = $baseUrl . '/std/admin/client/' . attr_url($puuid) . '/result/' . $assignmentItem->getResultId();
        } else {
            $smartUrl = $baseUrl . '/std/admin/client/' . attr_url($puuid) . '/asset-result/' . $assignmentItem->getResultId();
        }
        // TODO: @adunsulag let people choose the skins... this will need to match the themes eventually.
        $smartUrl .= "?seamless=true&skin=addo-blue-skin";

        $data = [
            'scriptPath' => $this->publicPath . "backend"
            ,'auditId' => $auditId
            ,'assignmentItem' => $assignmentItem
            ,'encounters' => $encounters ?? []
            ,'categoryTree' => $category
            ,'assignmentName' => $assignmentItem->getName()
            ,'smartUrl' => $smartUrl
        ];
        $body = $this->twig->render('discoverandchange/portal/audit/assignment-item-audit-view.html.twig', $data);
        return $response->withBody(ServiceContainer::getStreamFactory()->createStream($body));
    }

    /**
     * @param mixed $auditId
     * @param mixed $pid
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function displayAuditForAssignedQuestionnaire($auditId, $pid, AssignedQuestionnaire $assignmentItem)
    {

        $response = ServiceContainer::getResponseFactory()->createResponse(200, 'OK');
        /** @var array{questionnaire_response: string, questionnaire_name: ?string} $qrResponse */
        $qrResponse = $this->qrService->fetchQuestionnaireResponseByResponseId($assignmentItem->getResultId());

        $qrResponseContent = $qrResponse['questionnaire_response'];
        $qr = json_decode($qrResponseContent, true, 512, JSON_THROW_ON_ERROR);

        $answers = $this->qrService->flattenQuestionnaireResponse($qr, '|', '');
        $content = $this->qrService->buildQuestionnaireResponseHtml($answers, '|');

        $category = $this->getCategoryList();
        $encounterService = new EncounterService();
        $encounters = $encounterService->getEncountersForPatientByPid($pid);

        $data = [
            'content' => $content
            ,'patient' => ''
            ,'scriptPath' => $this->publicPath . "backend"
            ,'auditId' => $auditId
            ,'qr' => $qr
            ,'encounters' => $encounters ?? []
            ,'categoryTree' => $category
            ,'questionnaireTitle' => $qrResponse['questionnaire_name']
        ];
        $body = $this->twig->render('discoverandchange/portal/audit/questionnaire-audit-view.html.twig', $data);
        return $response->withBody(ServiceContainer::getStreamFactory()->createStream($body));
    }

    // TODO: @adunsulag look at abstracting this out into a separate service class for our documents.
    /**
     * @return array<mixed>
     */
    private function getCategoryList()
    {
        // we'd normally use something like:
        // $listBox = new \HTML_TreeMenu_Listbox($categoryTree, array("promoText" => xl('Move Document to Category:')));
        // $listBoxHtml = $listBox->toHtml();
        // as used in C_Document.class.php but that is heavily intertwined with the C_Document.class.php and has to do a
        // bunch of node conversions anyways... we want the flexibility of using twig to render the tree so we'll just
        // keep it the way we have right now.
        $category = new \CategoryTree(1);
        /** @var array<array-key, mixed> $categoryTreeNodes */
        $categoryTreeNodes = $category->tree;
        $root = $this->getCategoryTree($category, 1, (array) $categoryTreeNodes[1], 0);
        // we want to skip over the 'Categories' folder and just return the children
        return $root['tree'];
    }

    /**
     * @param mixed $currentNode
     * @param array<mixed> $children
     * @param int $depth
     * @return array{id: mixed, name: mixed, depth: int, tree: array<array-key, mixed>}
     */
    private function getCategoryTree(\CategoryTree $obj, $currentNode, $children, $depth = 0)
    {
        // do a breadth first descent of the tree
        /** @var array<string, mixed> $info */
        $info = $obj->get_node_info($currentNode);
        $transformedTree = [
            'id' => $currentNode
            ,'name' => $info['name']
            ,'depth' => $depth
            ,'tree' => []
        ];
        if ($children !== []) {
            foreach ($children as $key => $val) {
                if ($key === 0) {
                    continue; // not sure why we'd end up with empty 0 keys but we are skipping them.
                }
                $transformedTree['tree'][$key] = $this->getCategoryTree($obj, $key, (array) $val, $depth + 1);
            }
        }
        return $transformedTree;
    }

    /**
     * @param string $action
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function actionNotFound($action)
    {
        $response = ServiceContainer::getResponseFactory()->createResponse(404, 'Page not found');
        $body = $this->twig->render('error/404.html.twig');
        return $response->withBody(ServiceContainer::getStreamFactory()->createStream($body));
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function returnError(\Exception $exception)
    {
        $response = ServiceContainer::getResponseFactory()->createResponse(500, 'Internal Server Error');
        try {
            $body = $this->twig->render('error/500.html.twig', ['exception' => $exception]);
            return $response->withBody(ServiceContainer::getStreamFactory()->createStream($body));
        } catch (\Exception $exception) {
            // if we are having a problem with our twig rendering we are just going to return an invalid response
            return $response;
        }
    }

    /**
     * @param mixed $auditRecordId
     * @return void
     */
    private function updateOnSitePortalActivityWithCompletion($auditRecordId)
    {
        $sql = "UPDATE onsite_portal_activity SET pending_action='completed',status='closed' WHERE id = ? ";
        $binds = [$auditRecordId];
        QueryUtils::sqlStatementThrowException($sql, $binds);
    }
}
