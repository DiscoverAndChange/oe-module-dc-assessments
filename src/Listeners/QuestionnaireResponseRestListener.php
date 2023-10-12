<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners;

use OpenEMR\Events\Services\ServiceSaveEvent;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Logging\LoggerAwareTrait;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetResultBlobFHIRResourceService;
use OpenEMR\Validators\ProcessingResult;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class QuestionnaireResponseRestListener implements IStaticEventSubscriber
{
    use LoggerAwareTrait;

    public function __construct(private AssessmentResponseBlobFHIRResourceService $assessmentResponseBlobFHIRResourceService, private LibraryAssetResultBlobFHIRResourceService $libraryAssetResultBlobFHIRResourceService)
    {
    }
    public static function subscribeToEvents(Container $container, EventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->addListener('fhir.questionnaire_response.pre_insert', function (GenericEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->dispatchFHIRInsertEvent($event);
            }
        });
        $eventDispatcher->addListener('fhir.questionnaire_response.search', function (GenericEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->dispatchFHIRSearchEvent($event);
            }
        });
    }

    public function dispatchFHIRInsertEvent(GenericEvent $event)
    {
        // for now we stick with the generic event
        $fhirResource = $event->getSubject();
        $result = null;
        if ($fhirResource instanceof FHIRQuestionnaireResponse) {
            // grab the extension and see if we dispatch it to our response handlers
            $extension = $fhirResource->getExtension() ?? [];
            $extension = array_filter($extension, function ($item) {
                if (str_starts_with($item->getUrl(), "https://www.discoverandchange.com/fhir/openemr-")) {
                    return true;
                }
                return false;
            });
            if (!empty($extension)) {
                $extension = $extension[0];
                // we only go off the first one
                if ($extension->getUrl() == "https://www.discoverandchange.com/fhir/" . AssessmentResponseBlobFHIRResourceService::CODE_DAC_ASSESSMENT) {
                    $result = $this->assessmentResponseBlobFHIRResourceService->insert($fhirResource);
                } else if ($extension->getUrl() == "https://www.discoverandchange.com/fhir/" . LibraryAssetResultBlobFHIRResourceService::CODE_DAC_LIBRARY_ASSET) {
                    $result = $this->libraryAssetResultBlobFHIRResourceService->insert($fhirResource);
                }
            }
        }
        // we have something so let's return our processing result
        // TODO: @adunsulag eventually we want to formalize this event.
        if (!empty($result) && $result instanceof ProcessingResult) {
            $event->stopPropagation();
            $event->setArgument('result', $result);
        }
    }

    public function dispatchFHIRSearchEvent(GenericEvent $event)
    {
        // for now we stick with the generic event
        $fhirSearchParameters = $event->getSubject();
        $processingResult = new ProcessingResult();
        $result = $this->assessmentResponseBlobFHIRResourceService->getAll($fhirSearchParameters);
        if (!empty($result) && $result instanceof ProcessingResult) {
            $processingResult->addProcessingResult($result);
        } else {
            // we have something so let's return our processing result
            $this->getLogger()->errorLogCaller("Failed to process the search request for assessment response results.");
            $processingResult->addInternalError(xlt("Failed to process the search request."));
        }
        if ($processingResult->isValid()) {
            $result = $this->libraryAssetResultBlobFHIRResourceService->getAll($fhirSearchParameters);
            if (!empty($result) && $result instanceof ProcessingResult) {
                $processingResult->addProcessingResult($result);
            } else {
                // we have something so let's return our processing result
                $this->getLogger()->errorLogCaller("Failed to process the search request for asset library response results.");
                $processingResult->addInternalError(xlt("Failed to process the search request."));
            }
        }
        $event->setArgument('result', $processingResult);
        return $event;
    }
}
