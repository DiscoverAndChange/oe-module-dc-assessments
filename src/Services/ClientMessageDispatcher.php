<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Events\Messaging\SendNotificationEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Services\LogoService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ClientMessageDispatcher
{
    public function __construct(private SystemLogger $logger, private EventDispatcher $dispatcher, private HTMLSanitizer $sanitizer, private GlobalConfig $config)
    {
    }

    public function sendAssignmentsCompleteNotification(string $clientId, $patientPid)
    {
        if (!$this->config->shouldSendAssignmentCompletionNotices()) {
            return; // nothing to do here.
        }
        if ($this->config->shouldSendProviderNotification()) {
            $this->sendProviderNotification($clientId, $patientPid);
        }
        if ($this->config->getAssignmentCompletionNoticeUserId() != null) {
            $this->sendUserNotification($clientId, $patientPid, $this->config->getAssignmentCompletionNoticeUserId());
        }
    }

    private function sendProviderNotification($clientId, $patientPid)
    {
        $patientService = new PatientService();
        $patient = $patientService->findByPid($patientPid);
        if (!empty($patient['providerID'])) {
            $this->sendUserNotification($clientId, $patientPid, $patient['providerID']);
        }
    }

    private function sendUserNotification($clientId, $patientPid, $userId)
    {
        $userService = new UserService();
        $user = $userService->getUser($userId);
        if (empty($user)) {
            $this->logger->errorLogCaller("Failed to find user for assignment completion notice", ['userId' => $userId]);
            return;
        }
        if (empty($user['email'])) {
            $this->logger->errorLogCaller("User missing valid email address for assignment completion notice", ['userId' => $userId]);
            return;
        }

        $subject = xl("Assessments completed for your patient");
        $msg = [
            "email" => $user['email'],
            "url" => $this->config->getSmartAppAdminRootPath() . "std/admin/client/" . $clientId,
            "client" => $clientId
        ];
        $template = "discoverandchange/emails/assessment-services-assignments-complete";
        if (!$this->sendMessageViaMailer($subject, $user, $template, $msg)) {
            $this->logger->errorLogCaller("Failed to send assignment completion notice to user", ['userId' => $userId, 'puuid' => $clientId, 'pid' => $patientPid]);
        } else {
            $this->logger->debug(self::class . "->" . __FUNCTION__ . " - sent assignment completion notice to user", ['userId' => $userId, 'puuid' => $clientId, 'pid' => $patientPid]);
        }
    }

    public function sendInvitationMessage($patientPid, $subject, $message, $patientEmail, $senderEmail, $isTest)
    {
        $this->logger->debug(self::class . "->sendInvitationMessage() called", ['pid' => $patientPid]);
        // sanitize both the subject and the message
        $sanitizedMessage = $textMessage = $sanitizedSubject = null;
        try {
            // new lines converted on our message

            // we get our text only rendering...
            $textMessage = $this->sanitizer->stripHTML($message);
//            $sanitizedSubject = $this->stripHTML($subject);

            // now we want to convert text to new lines for our message,
            // then strip everything else out.
//            $nl2brMessage = nl2br($message);
//            $sanitizedMessage = $this->sanitizeString($nl2brMessage);
        } catch (\Exception $error) {
            $this->logger->errorLogCaller($error->getMessage(), ['trace' => $error->getTraceAsString()]);
            throw new \InvalidArgumentException("Failed to sanitize email text", ErrorCode::SYSTEM_ERROR);
        }

        $recipientEmail = $isTest ? $senderEmail : $patientEmail;
//        $finalSubject = $isTest ? xl("Test") . " - " + $sanitizedSubject : $sanitizedSubject;
        $notificationEvent = new SendNotificationEvent($patientPid, ['alt_content' => $textMessage, 'include_email' => true]);
        // let whatever systems are setup for sending notifications to handle this.
        $this->dispatcher->dispatch($notificationEvent, SendNotificationEvent::SEND_NOTIFICATION_BY_SERVICE);
    }

    private function sendMessageViaMailer($subject, $user, $template, $templateData)
    {
        if (empty($templateData['logo'])) {
            $logoService = new LogoService();
            $templateData['logo'] = $this->config->getGlobalSetting('qualified_site_addr') . $logoService->getLogo("core/login/primary");
            $templateData['logoAlt'] = $this->config->getGlobalSetting("openemr_name");
        }
        $from = $this->config->getGlobalSetting("practice_return_email_path");
        return \MyMailer::emailServiceQueueTemplatedEmail($from, $user['email'], $subject, $template, $templateData);
    }
}
