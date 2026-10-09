<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Common\Database\QueryUtils;
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


    /** @return void */
    public function updateQuestionnaireAssignments(ServiceSaveEvent $saveEvent)
    {
        if ($saveEvent->getService() instanceof QuestionnaireResponseService) {
            /** @var array{isNew: bool, patient_id: string, questionnaire_id: string, encounter: string, response_id: string, questionnaire_name: string} $data */
            $data = $saveEvent->getSaveData();
            $isNew = $data['isNew'] === true;
            if (!$isNew) {
                return; // nothing to do here as we don't update assignments on an update request.
            }
            $pid = $data['patient_id'];
            $patientService = new PatientService();
            $puuid = UuidRegistry::uuidToString($patientService->getUuid($pid));
            $questionnaireId = $data['questionnaire_id'];
            $encounter = $data['encounter'];
            try {
                QueryUtils::inTransaction(function () use ($pid, $encounter, $questionnaireId, $data, $puuid) {
                    if ($pid !== '') {
                        if ($encounter !== '') {
                            $items = $this->assignmentRepository->getQuestionnaireAssignmentItemsForEncounter($encounter, $questionnaireId);
                        } else {
                            $items = $this->assignmentRepository->getQuestionnaireAssignmentItemsForClient((int) $pid, $questionnaireId);
                        }
                        if ($items !== []) {
                            foreach ($items as $item) {
                                if (!$item->getIsComplete() && $item instanceof AssignedQuestionnaire) {
                                    // only the first incomplete questionnaire item is completed per event
                                    $this->updateAssignmentItem($item, $data, $puuid);
                                    break;
                                }
                            }
                        }
                    }
                });
            } catch (\Exception $exception) {
                // completion is best-effort: log and let the QR save succeed even if it fails
                ServiceContainer::getLogger()->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            }
        }
    }
    /**
     * @param array{isNew: bool, patient_id: string, questionnaire_id: string, encounter: string, response_id: string, questionnaire_name: string} $data
     * @param string $puuid
     */
    private function updateAssignmentItem(AssignedQuestionnaire $item, $data, $puuid): Assignment
    {
        $questionnaireId = $data['questionnaire_id'];
        $item->setResultId($data['response_id']);
//        $resourceService = new TaskOnsitePortalActivityAccessService();
//        $portalAuditId = $resourceService->createOnSitePortalActivity($puuid, 'dc-assignment', $data['questionnaire_name'], $item->getId());
//        $item->setAuditId($portalAuditId);
        // now create the pdf document
        // we stuff it in the In Review category
        /** @var string|int|null $categoryId */
        $categoryId = QueryUtils::fetchSingleValue("SELECT id FROM categories WHERE name = ?", 'id', ['Reviewed']);
        $category = (string) ($categoryId ?? 3);
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

    /** @return void */
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
