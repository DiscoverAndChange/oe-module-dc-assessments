<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;

class BackendDispatchController
{
    const RENDER_DIGITAL_DOCUMENTS = 'render-digital-documents';
    const RENDER_APPOINTMENT_NOTIFICATION = 'render-appointment-notification';

    const SEND_APPOINTMENT_NOTIFICATION = 'send-appointment-notification';

    const CONFIG = 'config';

    const CONFIG_IMPORT = 'config-import';

    public function __construct(private AssessmentAppointmentController $appointmentController, private ConfigController $configController)
    {
    }

    public function dispatch($action, $queryVars)
    {
        switch ($action) {
            case self::SEND_APPOINTMENT_NOTIFICATION:
                $request = $this->appointmentController->sendAppointmentNotification($queryVars['pc_eid']);
                break;
            case self::RENDER_DIGITAL_DOCUMENTS:
            case self::RENDER_APPOINTMENT_NOTIFICATION:
                $request = $this->appointmentController->renderWizardScreenForAppointmentId($action, $queryVars['pc_eid']);
                break;
            case self::CONFIG:
                $request = $this->configController->renderConfigAction($action, $queryVars);
                break;
            case self::CONFIG_IMPORT:
                $request = $this->configController->importConfigAction($action, $queryVars);
                break;
            default:
                (new SystemLogger())->errorLogCaller("Unknown action", ['action' => $action]);
                $request = RestUtils::getNotFoundResponse();
                break;
        }
        return $request;
    }
}
