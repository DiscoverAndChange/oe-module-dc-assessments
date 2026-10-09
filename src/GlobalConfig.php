<?php

/**
 * GlobalConfig file for configuring the custom module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Services\Globals\GlobalsService;

class GlobalConfig
{
    const DC_ASSESSMENTS_CONFIG_CLIENT_ID = "dac_assessments_smart_client_id";

    const DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG = 'dac_assessments_completion_send_notices_flag';

    const DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG = 'dac_assessments_completion_send_provider_notices_flag';

    const DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES = "dac_assessments_oauth2_layout_override";

    const DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID = 'dac_assessments_completion_address_book_id';
    const DC_ASSESSMENTS_CONFIG_PATIENT_CLIENT_ID = 'dc_assessments_smart_patient_client_id';

    // The confidential provider/admin SMART client. OpenEMR only grants user/* scopes to confidential
    // clients, so the EHR-launched provider app uses a separate client from the public patient client.
    const DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_ID = 'dc_assessments_smart_provider_client_id';
    const DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_SECRET = 'dc_assessments_smart_provider_client_secret';

    const MODULE_NAME = "oe-module-dc-assessments";
    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";
    public const INSTALLATION_NAME  = "openemr_name";

    private const LOCAL_DEBUG = false;

    /** @var array<string, mixed> */
    private array $globalsArray;

    /**
     * @var CryptoGen
     */
    private $cryptoGen;


    /** @param array<string, mixed> $globalsArray */
    public function __construct(array $globalsArray)
    {
        $this->globalsArray = $globalsArray;
        $this->cryptoGen = new CryptoGen();
    }

    /**
     * @param GlobalsService $service
     * @return void
     */
    public function setupConfiguration(GlobalsService $service)
    {
        $section = xlt("Discover and Change Assessments");
        $settings = $this->getGlobalSettingSectionConfiguration();

        foreach ($settings as $key => $config) {
            $value = $this->globalsArray[$key] ?? $config['default'];
            $setting = new GlobalSetting(
                xlt($config['title']),
                $config['type'],
                $value,
                xlt($config['description']),
                true
            );
            if (isset($config['options']) && $config['options'] !== []) {
                foreach ($config['options'] as $key => $option) {
                    $setting->addFieldOption($key, $option);
                }
            }
            $service->appendToSection(
                $section,
                $key,
                $setting
            );
        }
    }
    /**
     * Returns true if all of the settings have been configured.  Otherwise it returns false.
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function getSmartAppClientId()
    {
        return $this->getGlobalSetting(self::DC_ASSESSMENTS_CONFIG_CLIENT_ID);
    }

    /**
     * @param string $settingKey
     * @return mixed
     */
    public function getGlobalSetting($settingKey)
    {
        return $this->globalsArray[$settingKey] ?? null;
    }

    /**
     * @return array<string, array{title: string, description: string, type: string, default: string|bool, options?: array<string, string>}>
     */
    public function getGlobalSettingSectionConfiguration()
    {
        $settings = [
            self::DC_ASSESSMENTS_CONFIG_CLIENT_ID => [
                'title' => 'DAC Assessments Smart App Client ID'
                ,'description' => 'Registered Smart App Client (Remove if you wish to re-register)'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => ''
            ]
            ,self::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG => [
                'title' => 'Send Assignment Completion Notifications'
                ,'description' => 'Send email notice when patient has completed all assignments'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => false
            ]
            ,self::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG => [
                'title' => 'Notify Primary Care Provider on assignment completions'
                ,'description' => 'Notify primary care provider (if patient has one) when patient has completed all assignments'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => false
            ]
            ,self::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID => [
                'title' => 'Assignment Completion User To Notify (Address Book ID)'
                ,'description' => 'User to notify when patient has completed all assignments'
                ,'type' => GlobalSetting::DATA_TYPE_ADDRESS_BOOK
                ,'default' => false
            ]
            ,self::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES => [
                'title' => 'Override OAuth2 Login Pages with Improved Layout'
                ,'description' => 'Check to override the default OAuth2 login pages with improvements and design enhancements'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => '1'
            ]
        ];
        return $settings;
    }

    /**
     * @return mixed
     */
    public function getRootDir()
    {
        // note this comes from the variable in the global scope IE $GLOBALS defined in global.inc.php
        return $this->getGlobalSetting('webserver_root');
    }
    /**
     * @return string
     */
    public function getPublicPathFQDN()
    {
        // return the public path with the fully qualified domain name in it
        // qualified_site_addr already has the webroot in it.
        return OEGlobalsBag::getInstance()->get('qualified_site_addr') . $this->getGlobalSetting("web_root")
        . self::MODULE_INSTALLATION_PATH . self::MODULE_NAME .  '/public/';
    }

    /**
     * @return string
     */
    public function getPublicBackendPathFQDN()
    {
        return $this->getPublicPathFQDN() . "backend/";
    }

    /**
     * @return string
     */
    public function getPublicFrontendPathFQDN()
    {
        return $this->getPublicPathFQDN() . "frontend/";
    }

    /**
     * @return string
     */
    public function getSmartAppAdminLoginPublicPath()
    {
        return $this->getSmartAppAdminRootPath() . "login";
    }

    /**
     * @return string
     */
    public function getSmartAppAdminRootPath()
    {
        if (self::LOCAL_DEBUG) {
            return "http://localhost:4200/";
        }
        return $this->getPublicPathFQDN() . "frontend/";
    }

    /**
     * @return bool
     */
    public function shouldSendAssignmentCompletionNotices()
    {
        $shouldSendNotice = (OEGlobalsBag::getInstance()->get(self::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG) ?? '0') === '1';
        $contactUserId = $this->getAssignmentCompletionNoticeUserId() !== null;
        $providerNoticeEnabled = $this->shouldSendProviderNotification();
        return $shouldSendNotice && ($contactUserId || $providerNoticeEnabled);
    }

    /**
     * @return mixed
     */
    public function getAssignmentCompletionNoticeUserId()
    {
        return OEGlobalsBag::getInstance()->get(self::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID) ?? null;
    }

    /**
     * @return bool
     */
    public function shouldSendProviderNotification()
    {
        return (OEGlobalsBag::getInstance()->get(self::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG) ?? '0') === '1';
    }

    /**
     * @return string
     */
    public function getSmartAppAdminPublicPathRedirectUri()
    {
        if (self::LOCAL_DEBUG) {
            return "http://localhost:4200/loginFinalize";
        }
        return $this->getSmartAppAdminLoginPublicPath() . "Finalize";
    }

    /**
     * @return string
     */
    public function getSmartAppClientPublicPath()
    {
        if (self::LOCAL_DEBUG) {
            return "http://localhost:4200/login";
        }
        return $this->getPublicPathFQDN() . "frontend/login";
    }

    /**
     * @return string
     */
    public function getSmartAppClientPublicPathRedirectUri()
    {
        if (self::LOCAL_DEBUG) {
            return "http://localhost:4200/loginFinalize";
        }
        return $this->getSmartAppClientPublicPath() . "Finalize";
    }

    /**
     * @return string
     */
    public function getSmartAppPatientLaunchUri()
    {
        if (self::LOCAL_DEBUG) {
            return "http://localhost:4200/std/assessments/dashboard";
        }
        return $this->getPublicPathFQDN() . "frontend/std/assessments/dashboard";
    }

    /**
     * @return string
     */
    public function getFHIRUrl()
    {
        return (new ServerConfig())->getFhirUrl();
    }

    /**
     * @return string
     */
    public function getAPIUrl()
    {
        return (new ServerConfig())->getBaseApiUrl();
    }

    /**
     * @return mixed
     */
    public function getFHIRPatientClientId()
    {
        return $this->getGlobalSetting(self::DC_ASSESSMENTS_CONFIG_PATIENT_CLIENT_ID);
    }

    /**
     * @return string
     */
    public function getPatientClientRedirectUrl()
    {
        return $this->getPublicPathFQDN() . "index.php";
//        return $this->getGlobalSetting("web_root") . self::MODULE_INSTALLATION_PATH . "public/index.php";
    }

    /**
     * @return string
     */
    public function getPatientClientScopes()
    {
        return "launch/patient api:fhir openid profile patient/Task.read patient/Questionnaire.read";
    }

    /**
     * @return mixed
     */
    public function getQualifiedSiteAddress()
    {
        return $this->getGlobalSetting('qualified_site_addr');
    }

    /**
     * @return mixed
     */
    public function getPortalOnsiteAddress()
    {
        // return the portal address to be used.
        if ($this->getGlobalSetting('portal_onsite_two_basepath') == '1') {
            return $this->getQualifiedSiteAddress() . '/portal/patient';
        } else {
            return $this->getGlobalSetting('portal_onsite_two_address');
        }
    }

    /**
     * @return mixed
     */
    public function getApplicationName()
    {
        return $this->getGlobalSetting(self::INSTALLATION_NAME);
    }

    /**
     * @return mixed
     */
    public function getNotificationDefaultFrom()
    {
        return $this->getPatientReminderName();
    }

    /**
     * @return mixed
     */
    public function getNotificationDefaultReplyTo()
    {
        return $this->getPatientReminderName();
    }

    /**
     * @return mixed
     */
    private function getPatientReminderName()
    {
        return $this->getGlobalSetting('patient_reminder_sender_email');
    }

    /**
     * @return string
     */
    public function getSmartAppName()
    {
        return "Discover and Change Assessment Platform";
    }

    /**
     * @return string
     */
    public function getSmartAppContactAddress()
    {
        return 'info@discoverandchange.com';
    }

    /**
     * @return string
     */
    public function getSmartAppScopes()
    {
        // TODO: @adunsulag we need to look at breaking this up into a client scope app and an admin scope app.
        return 'fhirUser api:port api:oemr openid launch patient/Patient.read patient/Task.read patient/Task.write patient/Questionnaire.read patient/QuestionnaireResponse.read patient/QuestionnaireResponse.write user/Patient.read user/Task.read user/Questionnaire.read user/QuestionnaireResponse.read user/QuestionnaireResponse.write patient/patient.read user/reports.read patient/clients.read user/clients.read user/assignment-groups.write user/assignments.write user/assignments.write user/assessment-groups.read patient/assessment-groups.read user/assessment-groups.write user/assessments.write user/assessment-groups.write user/assessment-reports.read user/assessment-reports.write user/assessment-reports.read user/assessment-reports.write user/assessment-results.read user/assessment-results.write patient/assessment-results.write user/tags.read patient/tags.read user/message-templates.read user/assessment-users.read user/assessment-users.read user/library-assets.read user/library-assets.read patient/library-assets.read user/library-assets.write user/library-asset-results.write patient/library-asset-results.write user/library-asset-results.read user/assessments.read patient/assessments.read user/assessments.write patient/assessments.write user/assessments.read patient/assessments.read user/assessments.write user/announcements.read user/messages.write';
        //return 'launch user/Patient.read user/Task.read user/Questionnaire.read user/QuestionnaireResponse.read user/QuestionnaireResponse.write api:port api:oemr openid profile offline_access patient/patient.read user/reports.read patient/clients.read user/clients.read user/assignment-groups.write user/assignments.write user/assignments.write user/assessment-groups.read patient/assessment-groups.read user/assessment-groups.write user/assessments.write user/assessment-groups.write user/assessment-reports.read user/assessment-reports.write user/assessment-reports.read user/assessment-reports.write user/assessment-results.read user/assessment-results.write patient/assessment-results.write user/tags.read patient/tags.read user/message-templates.read user/assessment-users.read user/assessment-users.read user/library-assets.read user/library-assets.read patient/library-assets.read user/library-assets.write user/library-asset-results.write patient/library-asset-results.write user/library-asset-results.read user/assessments.read patient/assessments.read user/assessments.write patient/assessments.write user/assessments.read patient/assessments.read user/assessments.write user/announcements.read user/messages.write fhirUser';
    }

    /**
     * @param string $clientId
     * @return void
     */
    public function saveSmartAppClientId(string $clientId)
    {
        $this->saveGlobalSetting(self::DC_ASSESSMENTS_CONFIG_CLIENT_ID, $clientId);
    }

    /** Upsert a module global (gl_index 0). */
    private function saveGlobalSetting(string $name, string $value): void
    {
        if ($this->getGlobalSetting($name) === null) {
            $sql = "INSERT INTO globals ( gl_value, gl_index, gl_name ) VALUES ( ?, 0, ? )";
        } else {
            $sql = "UPDATE globals SET gl_value = ? WHERE gl_name = ?";
        }
        QueryUtils::sqlStatementThrowException($sql, [$value, $name]);
        // keep the in-memory snapshot in sync so a later read in the same request sees it
        $this->globalsArray[$name] = $value;
    }

    /** @return mixed */
    public function getProviderClientId()
    {
        return $this->getGlobalSetting(self::DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_ID);
    }

    /** @return void */
    public function saveProviderClientId(string $clientId)
    {
        $this->saveGlobalSetting(self::DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_ID, $clientId);
    }

    /** @return mixed */
    public function getProviderClientSecret()
    {
        return $this->getGlobalSetting(self::DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_SECRET);
    }

    /**
     * The confidential provider client's secret lives server-side only (never shipped to the browser);
     * the module brokers the provider token exchange with it.
     * @return void
     */
    public function saveProviderClientSecret(string $secret)
    {
        $this->saveGlobalSetting(self::DC_ASSESSMENTS_CONFIG_PROVIDER_CLIENT_SECRET, $secret);
    }

    /**
     * Provider (confidential, user-context) scopes: the user/* subset of the full scope list plus the
     * shared launch/openid scopes. No patient/* scopes -- those belong to the public patient client.
     * @return string
     */
    public function getSmartAppProviderScopes()
    {
        return $this->filterScopesByContext(['user/'], ['fhirUser', 'api:port', 'api:oemr', 'openid', 'launch']);
    }

    /**
     * Patient (public) scopes: the patient/* subset plus the shared launch/openid scopes.
     * @return string
     */
    public function getSmartAppPatientScopes()
    {
        return $this->filterScopesByContext(['patient/'], ['fhirUser', 'api:port', 'api:oemr', 'openid', 'launch']);
    }

    /**
     * @param list<string> $contextPrefixes
     * @param list<string> $sharedScopes
     * @return string
     */
    private function filterScopesByContext(array $contextPrefixes, array $sharedScopes)
    {
        $all = explode(' ', $this->getSmartAppScopes());
        $kept = [];
        foreach ($all as $scope) {
            if ($scope === '') {
                continue;
            }
            if (in_array($scope, $sharedScopes, true)) {
                $kept[$scope] = true;
                continue;
            }
            foreach ($contextPrefixes as $prefix) {
                if (str_starts_with($scope, $prefix)) {
                    $kept[$scope] = true;
                    break;
                }
            }
        }
        return implode(' ', array_keys($kept));
    }

    /**
     * @return bool
     */
    public function shouldDisplayUpdatedOAuthPages()
    {
        return $this->getGlobalSetting(self::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES) === '1';
    }
}
