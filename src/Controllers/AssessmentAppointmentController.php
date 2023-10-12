<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use Google\Service\AdMob\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Events\Appointments\AppointmentDialogCloseEvent;
use OpenEMR\Events\Appointments\AppointmentJavascriptEventNames;
use OpenEMR\Events\Appointments\AppointmentRenderEvent;
use OpenEMR\Events\Appointments\AppointmentSetEvent;
use OpenEMR\Events\Messaging\SendNotificationEvent;
use OpenEMR\Events\Services\ServiceDeleteEvent;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\SMART\SMARTLaunchToken;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\IStaticEventSubscriber;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedTemplateProfile;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Modules\FaxSMS\Events\NotificationEventListener;
use OpenEMR\Services\AppointmentService;
use OpenEMR\Services\DocumentTemplates\DocumentTemplateService;
use OpenEMR\Services\ListService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\TokenSearchField;
use PHPUnit\Framework\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Twig\Environment;

class AssessmentAppointmentController implements IStaticEventSubscriber
{
    public function __construct(private Environment $twig, private AssignmentRepository $repository, private EventDispatcher $dispatcher, private GlobalConfig $config, private SmartAppClientService $appClientService)
    {
    }

    public static function subscribeToEvents(Container $container, EventDispatcherInterface $dispatcher)
    {

        $dispatcher->addListener(AppointmentRenderEvent::RENDER_BEFORE_ACTION_BAR, function (AppointmentRenderEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->renderDigitalDocumentsSection($event);
            }
        });
//        $dispatcher->addListener(AppointmentSetEvent::EVENT_HANDLE, [$this, 'sendNotificationMessages'], 20);
        $dispatcher->addListener(ServiceDeleteEvent::EVENT_PRE_DELETE, function (ServiceDeleteEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->deleteDigitalDocumentsSection($event);
            }
        });
        $dispatcher->addListener(AppointmentDialogCloseEvent::EVENT_NAME, function (AppointmentDialogCloseEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->renderAppointmentWizardScreens($event);
            }
        });
    }

    public function deleteDigitalDocumentsSection(ServiceDeleteEvent $deleteEvent)
    {
        if ($deleteEvent->getService() instanceof AppointmentService) {
            $apptId = $deleteEvent->getRecordId();
            try {
                $assignments = $this->repository->getAssignmentsForAppointmentId($apptId);
                array_map($assignments, function (Assignment $assignment) {
                    $this->repository->removeAssignment($assignment->getClientId(), $assignment->getId(), $_SESSION['authUserId']);
                });
            } catch (\Exception $e) {
                (new SystemLogger())->errorLogCaller(
                    'Failed to delete digital documents section for appointment id',
                    ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'recordId' => $apptId]
                );
            }
        }
    }

    private function hasWizardScreens(AppointmentDialogCloseEvent $event)
    {
        return $this->getWizardScreenFromCurrentRequest() !== null;
    }

    private function getWizardScreenFromCurrentRequest()
    {
        // checkbox for sending digital documents
        if (!empty($_POST['dc_add_edit_event_digital_documents'])) {
            // coming from the add_edit_event page
            return BackendDispatchController::RENDER_DIGITAL_DOCUMENTS;
            // checkbox for sending notification
        } else if (!empty($_POST['dc_add_edit_event_send_notification'])) {
            return BackendDispatchController::RENDER_APPOINTMENT_NOTIFICATION;
        }
        if (!empty($_GET['action']) && $_GET['action'] == BackendDispatchController::RENDER_APPOINTMENT_NOTIFICATION) {
            return BackendDispatchController::RENDER_APPOINTMENT_NOTIFICATION;
        }
        return null;
    }
    public function renderWizardScreenForAppointmentId($wizardScreen, $appointmentId)
    {
        if ($wizardScreen == BackendDispatchController::RENDER_DIGITAL_DOCUMENTS) {
            $this->renderDigitalDocumentsScreen($appointmentId);
        } else if ($wizardScreen == BackendDispatchController::RENDER_APPOINTMENT_NOTIFICATION) {
            $this->renderAppointmentNotificationScreen($appointmentId);
        }
    }

    public function renderAppointmentWizardScreens(AppointmentDialogCloseEvent $event)
    {
        $appointmentId = $event->getAppointmentId();
        if (empty($appointmentId)) {
            return;
        }
        if ($this->hasWizardScreens($event)) {
            $event->stopPropagation(); // don't let the current event in add_edit continue on.
            $this->renderWizardScreenForAppointmentId($this->getWizardScreenFromCurrentRequest(), $appointmentId);
        }
    }

    private function renderAppointmentNotificationScreen($appointmentId, $displayMessage = null)
    {
        $appointmentService = new AppointmentService();
        $appointment = $appointmentService->getAppointment($appointmentId);
        if (empty($appointment) || empty($appointment[0]['pc_pid'])) { // patient appointment
            return; // nothing to do here
        }
        $appointment = $appointment[0];

        try {
            $docService = new DocumentTemplateService();
            $templateList = $docService->getTemplateListByCategory('notification_template', '-1', 'Default Notification');
            $defaultMessage = '';
            if (!empty($templateList)) {
                $message = $templateList['template_content'] ?? '';
            } else {
                $message = xl('You have been assigned new documents to complete on your patient portal.');
            }

            $patientService = new PatientService();
            $patient = $patientService->findByPid($appointment['pc_pid']);
            $phone = $patient['phone_cell'] ?? null;
            $email = $patient['email'] ?? null;
            $hipaaAllowEmail = ($patient['hipaa_allowemail'] ?? 'NO') === 'YES';
            $hipaaAllowSms = ($patient['hipaa_allowsms'] ?? 'NO') === 'YES';
            $noContactMethods = empty($phone) && empty($email) && !($hipaaAllowEmail || $hipaaAllowSms);
            $display = xl("Setup Notifications");
            $truncatedDisplay = mb_strimwidth($display, 0, 80, "...");
            if (empty($_GET['previous_step'])) { // no previous step we are going back to the calendar
                $backUrl = $this->getCalendarEventBackUrl($appointment);
            } else {
                // currently the only other step is the documents... if more wizards steps are added we'd handle this.
                $backUrl = $this->config->getPublicBackendPathFQDN() . "index-backend.php?action="
                    . urlencode(BackendDispatchController::RENDER_DIGITAL_DOCUMENTS)
                    . "&pc_eid=" . urlencode($appointment['pc_eid'])
                    . "&dc_add_edit_event_send_notification=1";
            }
            $nextStepUrl = null;
            $nextStepTitle = null;

            $data =  [
                'appt' => $appointment
                , 'assignment' => null
                , 'sendNotification' => false // default checked
                ,'noContactMethods' => $noContactMethods
                ,'sendPatientEmail' => $hipaaAllowEmail
                ,'sendPatientSMS' => $hipaaAllowSms
                ,'patient' => [
                    'email' => $email
                    ,'phone' => $phone
                ]
                ,'defaultMessage' => $message
                ,'headerText' => $truncatedDisplay
                ,'backUrl' => $backUrl
                ,'nextStepUrl' => $nextStepUrl
                ,'nextStepTitle' => $nextStepTitle
                ,'notificationPostUrl' => $this->getNotificatioNextStepUrl($appointment['pc_eid'], BackendDispatchController::SEND_APPOINTMENT_NOTIFICATION)
                ,'actionMessage' => $displayMessage
            ];
            echo $this->twig->render(
                "discoverandchange/appointment/add_edit_event_notifications_wizard.html.twig",
                $data
            );
        } catch (\Exception $e) {
            (new SystemLogger())->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString(), 'appt' => $appt ?? '']);
        }
    }

    private function getNotificatioNextStepUrl($pc_eid, $action = BackendDispatchController::RENDER_APPOINTMENT_NOTIFICATION)
    {
        return $this->config->getPublicBackendPathFQDN() . "index-backend.php?action="
            . urlencode($action)
            . "&pc_eid=" . urlencode($pc_eid)
            . "&previous_step=" . urlencode(BackendDispatchController::RENDER_DIGITAL_DOCUMENTS);
    }

    private function getCalendarEventBackUrl($appointment)
    {
        $linkDate = preg_replace("/-/", "", $appointment['pc_eventDate']);
        $backUrl = $GLOBALS['webroot'] . '/interface/main/calendar/add_edit_event.php?date='
            . urlencode($linkDate) . '&eid=' . urlencode($appointment['pc_eid']) . '&prov=';
        return $backUrl;
    }

    private function renderDigitalDocumentsScreen($appointmentId)
    {
        if (!empty($appointmentId)) {
            $smartAppService = $this->appClientService;
            $clientId = $smartAppService->getRegisteredClientId();
            $url = $GLOBALS['webroot'] . '/interface/smart/ehr-launch-client.php?intent=' . urlencode(SMARTLaunchToken::INTENT_APPOINTMENT_DIALOG)
                . '&client_id=' . urlencode($clientId) . "&csrf_token=" . urlencode(CsrfUtils::collectCsrfToken())
                . '&appointment_id=' . urlencode($appointmentId);
            $appointmentService = new AppointmentService();
            $appointment = $appointmentService->getAppointment($appointmentId);
            if (!empty($appointment) && !empty($appointment[0]['pc_pid'])) { // patient appointment
                $appointment = $appointment[0];
                $display = xl("Assign Digital Documents");
                $truncatedDisplay = mb_strimwidth($display, 0, 80, "...");

                $backUrl = $this->getCalendarEventBackUrl($appointment);
                $nextStepUrl = null;
                $nextStepTitle = xl('Configure Notifications');
                if (!empty($_REQUEST['dc_add_edit_event_send_notification'])) {
                    $nextStepUrl = $this->getNotificatioNextStepUrl($appointment['pc_eid']);
                }
                echo $this->twig->render('discoverandchange/appointment/add_edit_event_documents_wizard.html.twig', [
                    'ehrLaunchUrl' => $url,
                    'backUrl' => $backUrl,
                    'headerText' => $truncatedDisplay
                    ,'nextStepUrl' => $nextStepUrl
                    ,'nextStepTitle' => $nextStepTitle
                ]);
                return;
            }
        }
    }

    public function sendAppointmentNotification($pc_eid)
    {
        // no notification message to send so just return
        try {
            if (CsrfUtils::verifyCsrfToken($_POST['csrf_token']) === false) {
                throw new \InvalidArgumentException("CSRF token mismatch");
            }
            // TODO: @adunsulag is there an ACL for sending messages to patients?
            $service = new AppointmentService();
            $appt = $service->getAppointment($pc_eid);
            $appt = $appt[0] ?? [];
            $patientPid = $appt['pc_pid'] ?? null;
            if (empty($patientPid)) {
                // nothing to do here so we skip it
                return RestUtils::returnSingleObjectResponse(['type' => 'error']);
            }
            $message = $_POST['dc_appointments_notification_message'] ?? '';
            if (empty($message)) {
                $docService = new DocumentTemplateService();
                $templateList = $docService->getTemplateListByCategory('notification_template', '-1', 'Default Notification');
                if (!empty($templateList)) {
                    $message = $templateList['template_content'] ?? '';
                } else {
                    $message = xl('You have been assigned new documents to complete on your patient portal.');
                }
            }
            // now we need to add our message to take the assignments.
            $takeTest = xl("Complete your appointment documents at the following link ") . $this->config->getSmartAppPatientLaunchUri();
            $finalMessage = $message . " " . $takeTest;
            // for deliverability we need to make sure we don't go over 320 characters

            // note strlen returns the number of bytes if we are working with translated unicode characters which is
            // what we want as we don't want to exceed 320 bytes for our message
           // TODO: @adunsulag @sjpadgett do we care if we exceed 320 bytes?  If so we need to truncate the message

            // TODO: @adunsulag wire up our email notification message to take the assessment documents.
            $notificationEvent = new SendNotificationEvent($patientPid, ['alt_content' => $finalMessage]);
            $this->dispatcher->dispatch($notificationEvent, SendNotificationEvent::SEND_NOTIFICATION_BY_SERVICE);
        } catch (\Exception $e) {
            (new SystemLogger())->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return RestUtils::returnSingleObjectResponse(['type' => 'error']);
        }
        return RestUtils::returnSingleObjectResponse(['type' => 'success']);
    }

    public function renderDigitalDocumentsSection(AppointmentRenderEvent $event)
    {
        // I don't like that I have to hit the query vars to find out if this is a provider or group appointment
        // documents don't make sense on provider, and group isn't supported right now.
        if (!(empty($_GET['prov']) && empty($_GET['group']))) {
            return;
        }
        $assignment = null;
        try {
            $appt = $event->getAppt();
            $assignmentIds = $this->repository->getAssignmentUuidsForAppointment($appt['pc_eid']);
            $assignments = [];
            if (!empty($assignmentIds)) {
                $assignments = $this->repository->search([new TokenSearchField('assignment_uuid', $assignmentIds, true)]);
            }
            $assignmentItemsCount = 0;
            foreach ($assignments as $assignment) {
                $assignmentItemsCount += count($assignment->getItems());
            }
            echo $this->twig->render(
                "discoverandchange/appointment/add_edit_event_documents.html.twig",
                [
                    'appt' => $appt
                    , 'assignments' => $assignments
                    , 'itemCount' => $assignmentItemsCount
                ]
            );
        } catch (\Exception $e) {
            (new SystemLogger())->errorLogCaller($e->getMessage(), ['trace' => $e->getTraceAsString(), 'appt' => $appt ?? '']);
        }

        $this->renderNotificationsSection($event, $appt, $assignment);
    }


    public function renderNotificationsSection(AppointmentRenderEvent $event, array $appt, ?Assignment $assignment)
    {
        echo $this->twig->render("discoverandchange/appointment/add_edit_event_notifications.html.twig", []);
    }
}
