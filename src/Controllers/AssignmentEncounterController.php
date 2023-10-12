<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Events\Encounter\EncounterFormsListRenderEvent;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\SMART\SmartLaunchController;
use OpenEMR\FHIR\SMART\SMARTLaunchToken;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\IStaticEventSubscriber;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Services\EncounterService;
use OpenEMR\Services\PatientService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class AssignmentEncounterController implements IStaticEventSubscriber
{
    public function __construct(private Environment $twig, private AssignmentRepository $repository, private GlobalConfig $globalConfig, private SystemLogger $logger)
    {
    }

    public static function subscribeToEvents(Container $container, EventDispatcherInterface $eventDispatcher)
    {

        $eventDispatcher->addListener(
            EncounterFormsListRenderEvent::EVENT_SECTION_RENDER_POST,
            function (EncounterFormsListRenderEvent $event) use ($container) {
                $service = $container->get(self::class);
                if ($service instanceof self) {
                    $service->renderAssignmentListSection($event);
                }
            }
        );
    }

    public function renderAssignmentListSection(EncounterFormsListRenderEvent $event)
    {
        // we don't handle group and other types of encounters for now
        if ($event->getAttendantType() != 'pid') {
            return;
        }

        $pid = $event->getPid();
        $encounterId = $event->getEncounter();

        if (empty($pid) || empty($encounterId)) {
            $this->logger->errorLogCaller("Missing pid or encounterId");
        }
        $patientService = new PatientService();
        $puuid = $patientService->getUuid($pid);
        if (empty($puuid)) {
            $this->logger->errorLogCaller("Missing patient uuid");
        } else {
            $puuid = UuidRegistry::uuidToString($puuid);
        }
        $euuid = EncounterService::getUuidById($encounterId, EncounterService::ENCOUNTER_TABLE, 'encounter');
        if (empty($euuid)) {
            $this->logger->errorLogCaller("Missing encounter uuid");
        }
        $euuid = UuidRegistry::uuidToString($euuid);
//        $token = new SMARTLaunchToken($puuid, $euuid);
//        $token->setIntent(SMARTLaunchToken::INTENT_ENCOUNTER_DIALOG);
//        $launchCode = $token->serialize();
        $assignments = $this->repository->getAssignmentsForEncounterUuid($euuid, $puuid);
        $issuer = (new ServerConfig())->getFhirUrl();
//        $launchParams = "?launch=" . urlencode($launchCode) . "&iss=" . urlencode($issuer) . "&aud=" . urlencode($issuer);
//        $launchUri = $this->globalConfig->getSmartAppAdminPublicPath() . $launchParams;
        $name = $this->globalConfig->getSmartAppName();
        $data = [
            'pid' => $pid
            , 'encounterId' => $encounterId
            , 'assignments' => $assignments
//            ,'launchUri' => $launchUri
            ,'intent' => SMARTLaunchToken::INTENT_ENCOUNTER_DIALOG
            ,'clientName' => $name
            ,'clientId' => $this->globalConfig->getSmartAppClientId()
        ];

        echo $this->twig->render(
            "discoverandchange/encounter/assignment-encounter-section.html.twig",
            $data
        );
    }
}
