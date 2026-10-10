<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners;

use OpenEMR\Events\Services\ServiceSaveEvent;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaire;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Logging\LoggerAwareTrait;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetResultBlobFHIRResourceService;
use OpenEMR\Services\FHIR\FhirServiceBase;
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
    /** @return void */
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

    /** @return void */
    public function dispatchFHIRInsertEvent(GenericEvent $event)
    {
        // for now we stick with the generic event
        $fhirResource = $event->getSubject();
        $result = null;
        if ($fhirResource instanceof FHIRQuestionnaireResponse) {
            // grab the extension and see if we dispatch it to our response handlers
            // getExtension() already returns a (possibly empty) array, so the
            // ?? [] fallback was dead; drop it to satisfy the null-coalesce rule.
            $extension = $fhirResource->getExtension();
            $extension = array_filter($extension, function ($item) {
                if (str_starts_with($item->getUrl(), "https://www.discoverandchange.com/fhir/openemr-")) {
                    return true;
                }
                return false;
            });
            if ($extension !== []) {
                // array_filter preserves original keys, so the matching extension is not
                // necessarily at index 0 (a non-DAC extension may precede it). Reindex
                // before taking the first match, otherwise routing silently fails.
                $extension = array_values($extension)[0];
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
        if ($result !== null) {
            $event->stopPropagation();
            $event->setArgument('result', $result);
        }
    }

    /** @return mixed */
    public function dispatchFHIRSearchEvent(GenericEvent $event)
    {
        // for now we stick with the generic event
        /** @var array<mixed> $fhirSearchParameters */
        $fhirSearchParameters = $event->getSubject();
        $processingResult = new ProcessingResult();
        // getAll() always returns a ProcessingResult (errors are carried inside it, never by a
        // null/falsy return), so the old "else" error branches were unreachable dead code;
        // addProcessingResult already merges any internal errors from the sub-result.
        //
        // Only forward the search fields each blob sub-service actually supports. The parent
        // QuestionnaireResponse resource now declares IPatientCompartmentResourceService, so core
        // injects a `patient` parameter (via getOne()/patient-context binding) that these blob
        // services do not define -- passing it straight through made createOpenEMRSearchParameters()
        // throw SearchFieldException("This search field does not exist or is not supported"), which
        // surfaced as a 400 on QuestionnaireResponse create (insert -> representation getOne).
        $result = $this->assessmentResponseBlobFHIRResourceService->getAll(
            $this->filterToSupportedParams($this->assessmentResponseBlobFHIRResourceService, $fhirSearchParameters)
        );
        $processingResult->addProcessingResult($result);
        if ($processingResult->isValid()) {
            $result = $this->libraryAssetResultBlobFHIRResourceService->getAll(
                $this->filterToSupportedParams($this->libraryAssetResultBlobFHIRResourceService, $fhirSearchParameters)
            );
            $processingResult->addProcessingResult($result);
        }
        $event->setArgument('result', $processingResult);
        return $event;
    }

    /**
     * Restrict the FHIR search parameters to only those the given sub-service declares, so a field
     * the parent resource supports (e.g. `patient`) is not passed to a sub-service that does not,
     * which would raise a SearchFieldException.
     *
     * @param array<mixed> $fhirSearchParameters
     * @return array<mixed>
     */
    private function filterToSupportedParams(FhirServiceBase $service, array $fhirSearchParameters): array
    {
        $supported = $service->getSearchParams();
        if (!is_array($supported)) {
            return [];
        }
        return array_intersect_key($fhirSearchParameters, $supported);
    }
}
