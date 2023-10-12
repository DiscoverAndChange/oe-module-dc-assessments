<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Events\Services\ServiceSaveEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskOnsitePortalActivityAccessService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestionnaireAssignmentListener implements IStaticEventSubscriber
{
    public function __construct(private AssignmentRepository $assignmentRepository, private QuestionnaireResponseOnSiteDocumentService $questionnaireResponsePDFService)
    {
    }


    public function updateQuestionnaireAssignments(ServiceSaveEvent $saveEvent)
    {
        if ($saveEvent->getService() instanceof QuestionnaireResponseService) {
            $commitTransaction  = false;
            $data = $saveEvent->getSaveData();
            $isNew = $data['isNew'] === true;
            if (!$isNew) {
                return; // nothing to do here as we don't update assignments on an update request.
            }
            $pid = $data['patient_id'] ?? null;
            $patientService = new PatientService();
            $puuid = UuidRegistry::uuidToString($patientService->getUuid($pid));
            $questionnaireId = $data['questionnaire_id'];
            $encounter = $data['encounter'];
            QueryUtils::startTransaction();
            try {
                if (!empty($pid)) {
                    if (!empty($encounter)) {
                        $items = $this->assignmentRepository->getQuestionnaireAssignmentItemsForEncounter($encounter, $questionnaireId);
                    } else {
                        $items = $this->assignmentRepository->getQuestionnaireAssignmentItemsForClient($pid, $questionnaireId);
                    }
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            if (!$item->getIsComplete() && $item instanceof AssignedQuestionnaire) {
                                $assignment = $this->updateAssignmentItem($item, $data, $puuid);
                                $commitTransaction = true;
                                return $assignment;
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
                $commitTransaction = false;
            } finally {
                if ($commitTransaction) {
                    QueryUtils::commitTransaction();
                } else {
                    QueryUtils::rollbackTransaction();
                }
            }
        }
    }
    private function updateAssignmentItem(AssignedQuestionnaire $item, $data, $puuid): Assignment
    {
        $questionnaireId = $data['questionnaire_id'];
        $item->setResultId($data['response_id']);
//        $resourceService = new TaskOnsitePortalActivityAccessService();
//        $portalAuditId = $resourceService->createOnSitePortalActivity($puuid, 'dc-assignment', $data['questionnaire_name'], $item->getId());
//        $item->setAuditId($portalAuditId);
        // now create the pdf document
        // we stuff it in the In Review category
        $category = QueryUtils::fetchSingleValue("SELECT id FROM categories WHERE name = ?", 'id', ['Reviewed']) ?: 3;
        $document = $this->questionnaireResponsePDFService->createDocument(
            $item->getDocumentTemplateId(),
            $category,
            $data,
            $data['questionnaire_name']
        );
        $item->setDocumentId(UuidRegistry::uuidToString($document->get_uuid()));
        // grab the first one and let's complete it
        return $this->assignmentRepository->updateCompletedAssignmentItem($item);
    }

    public static function subscribeToEvents(Container $container, EventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->addListener(ServiceSaveEvent::EVENT_POST_SAVE, function (ServiceSaveEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->updateQuestionnaireAssignments($event);
            }
        });
    }
}
