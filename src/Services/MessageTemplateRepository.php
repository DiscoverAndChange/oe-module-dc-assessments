<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use Twig\Environment;

class MessageTemplateRepository
{
    public function __construct(private Environment $twig, private GlobalConfig $globalConfig)
    {
    }

    /**
     * Given a client and company, retrieve the invitation message template.
     * @param Patient $client
     * @param Company|Facility $company
     * @return array
     */
    public function getTemplateForClient(array $client, $company = null): array
    {

        // this could come from the database at some point
        $templateSettings = $this->getTemplateSettingsForFacility($client, $company);
        $defaultTemplate = $templateSettings["template"];
        return ["subject" => $templateSettings["subject"], "message" => $defaultTemplate];
    }

    /**
     * Retrieves the subject and template settings for the invitation message for the provided company.
     * @param array $company
     * @return array
     */
    public function getTemplateSettingsForFacility(array $client, ?array $company): array
    {
        // for now until we make it DB driven we are going to put this here
        // TODO: stephen make this driven by a value from the database.
        $data = [
            "client_display_name" => trim(($client['fname'] ?? '') . ' ' . ($client['lname'] ?? '')),
            "patient_portal_url" => $this->globalConfig->getSmartAppPatientLaunchUri()
        ];
        $defaultTemplate = $this->twig->render("discoverandchange/notifications/patient-complete-tasks.text.twig", $data);
        $defaultSubject = $this->globalConfig->getApplicationName() . '-' . xl('Portal Information');
//      TODO: @adunsulag if we want to use the facility name in the subject we can do it like this.
//        if (isset($company["name"])) {
//            $defaultSubject = $company["name"] . '-' . xl('Portal Information');
//        }

        return ["subject" => $defaultSubject, "template" => $defaultTemplate];
    }
}
