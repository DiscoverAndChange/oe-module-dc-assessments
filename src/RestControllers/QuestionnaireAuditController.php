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
use OpenEMR\Common\Http\Psr17Factory;
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
            $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            return $this->returnError($exception);
        }
    }

    // TODO: Is there a way we can just move this to our standard apis...
    private function actionChartAssignmentToEncounter($queryVars)
    {
        // action chart questionnaire to encounter
        /**
         * auditRecordId: event.target.dataset.recordId
        ,encounterId: eid
        ,csrfToken: csrfToken
         */
        $transactionCommitted = false;
        try {
            // we do this before we start the transaction to avoid autocommits during the service.
            $encounterService = new EncounterService();
            QueryUtils::startTransaction();

            $phpInput = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            $auditRecordId = $phpInput['auditRecordId'] ?? null;
            $encounterId = $phpInput['encounterId'] ?? null;
            $csrfToken = $phpInput['csrfToken'] ?? null;
            if (empty($auditRecordId) || empty($encounterId) || empty($csrfToken)) {
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
            if (empty($auditRecord) || $auditRecord[0]['activity'] !== 'dc-assignment') {
                throw new \InvalidArgumentException('Invalid recordId', ErrorCode::INVALID_REQUEST);
            }

            $assignmentItems = $this->assignmentRepository->getAssignmentItemsForAuditId($auditRecordId);
            if (empty($assignmentItems)) {
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
            $formId = $this->saveEncounterForm($auditRecord, $questionnaire, $qr, $result->getData()[0]['pid'], $encounterId);

            // now we need to mark the audit record as locked and saved.
            $this->updateOnSitePortalActivityWithCompletion($auditRecord['id']);

            QueryUtils::commitTransaction();
            $transactionCommitted = true;
            return RestUtils::returnSingleObjectResponse(['formid' => $formId]);
        } catch (\Exception $exception) {
            return RestUtils::getErrorResponse($this->logger, $exception);
        } finally {
            if (!$transactionCommitted) {
                try {
                    QueryUtils::rollbackTransaction();
                } catch (\Exception $exception) {
                    // if we can't rollback we just log and ignore.
                    $this->logger->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
                }
            }
        }
    }

    private function saveEncounterForm($auditRecord, $questionnaire, $questionnaireResponse, $pid, $encounterId)
    {
        // how is the encounter form saved
        $metaData = $this->qrService->extractResponseMetaData($questionnaireResponse, true);
        $formQuestionnaireAssessment = new FormQuestionnaireAssessment();
        $formQuestionnaireAssessment->setEncounter($encounterId);
        $formQuestionnaireAssessment->setPid($pid);
        $formQuestionnaireAssessment->setCopyright($qJSON['copyright'] ?? '');
        $formQuestionnaireAssessment->setFormName($auditRecord['narrative'] ?? '');
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

    private function actionView($queryVars)
    {
        // TODO: check that pid, recordId, and qr are set otherwise throw invalidargumentexception


        if (empty($queryVars['recordId'])) {
            return $this->actionNotFound('view');
        }
        $assignmentItems = $this->assignmentRepository->getAssignmentItemsForAuditId($queryVars['recordId']);
        // for now there should only be one audit to one assignment item
        $assignmentItem = $assignmentItems[0] ?? null;
        // for now we are just handling questionnaires
        if (empty($assignmentItem)) {
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
        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(200, 'OK');
        return $response->withBody($psrFactory->createStream($body));
    }
    private function displayAuditForSmartAppAssignment($auditId, $pid, Assignment $assignmentItem)
    {
        $category = $this->getCategoryList();
        $encounterService = new EncounterService();
        $patientService = new PatientService();
        $puuid = UuidRegistry::uuidToString($patientService->getUuid($pid));
        $encounters = $encounterService->getEncountersForPatientByPid($pid);

        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(200, 'OK');

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
        return $response->withBody($psrFactory->createStream($body));
    }

    private function displayAuditForAssignedQuestionnaire($auditId, $pid, AssignedQuestionnaire $assignmentItem)
    {

        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(200, 'OK');
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
        return $response->withBody($psrFactory->createStream($body));
    }

    // TODO: @adunsulag look at abstracting this out into a separate service class for our documents.
    private function getCategoryList()
    {
        // we'd normally use something like:
        // $listBox = new \HTML_TreeMenu_Listbox($categoryTree, array("promoText" => xl('Move Document to Category:')));
        // $listBoxHtml = $listBox->toHtml();
        // as used in C_Document.class.php but that is heavily intertwined with the C_Document.class.php and has to do a
        // bunch of node conversions anyways... we want the flexibility of using twig to render the tree so we'll just
        // keep it the way we have right now.
        $category = new \CategoryTree(1);
        $root = $this->getCategoryTree($category, 1, $category->tree[1], 0);
        // we want to skip over the 'Categories' folder and just return the children
        return $root['tree'] ?? [];
    }

    private function getCategoryTree(\CategoryTree $obj, $currentNode, $children, $depth = 0)
    {
        // do a breadth first descent of the tree
        $info = $obj->get_node_info($currentNode);
        $transformedTree = [
            'id' => $currentNode
            ,'name' => $info['name']
            ,'depth' => $depth
            ,'tree' => []
        ];
        if (!empty($children)) {
            foreach ($children as $key => $val) {
                if ($key === 0) {
                    continue; // not sure why we'd end up with empty 0 keys but we are skipping them.
                }
                $transformedTree['tree'][$key] = $this->getCategoryTree($obj, $key, $val, $depth + 1);
            }
        }
        return $transformedTree;
    }

    private function actionNotFound($action)
    {
        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(404, 'Page not found');
        $body = $this->twig->render('error/404.html.twig');
        return $response->withBody($psrFactory->createStream($body));
    }

    private function returnError(\Exception $exception)
    {
        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(500, 'Internal Server Error');
        try {
            $body = $this->twig->render('error/500.html.twig', ['exception' => $exception]);
            return $response->withBody($psrFactory->createStream($body));
        } catch (\Exception $exception) {
            // if we are having a problem with our twig rendering we are just going to return an invalid response
            return $response;
        }
    }

    private function updateOnSitePortalActivityWithCompletion($auditRecordId)
    {
        $sql = "UPDATE onsite_portal_activity SET pending_action='completed',status='closed' WHERE id = ? ";
        $binds = [$auditRecordId];
        QueryUtils::sqlStatementThrowException($sql, $binds);
    }
}
