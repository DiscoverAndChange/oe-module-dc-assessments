<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for GlobalConfig.
 *
 * Only the pure methods are exercised: constant getters, lookups against the
 * injected $globalsArray, $GLOBALS-driven URL/path building and the notification
 * flag logic. A handful of $GLOBALS keys are set directly (and restored in
 * tearDown) for the path-building and notification methods.
 *
 * Deferred (require DB / external runtime, intentionally NOT tested):
 *  - setupConfiguration(): needs a GlobalsService + xlt() translation runtime.
 *  - getFHIRUrl() / getAPIUrl(): construct ServerConfig, which reads OpenEMR
 *    server config (DB/$GLOBALS-backed).
 *  - saveSmartAppClientId(): issues INSERT/UPDATE via QueryUtils (DB write).
 */
class GlobalConfigTest extends TestCase
{
    /** @var array<string, array{set: bool, value: mixed}> */
    private array $savedGlobals = [];

    private const TOUCHED_KEYS = [
        'qualified_site_addr',
        GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG,
        GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG,
        GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID,
    ];

    protected function setUp(): void
    {
        foreach (self::TOUCHED_KEYS as $key) {
            $this->savedGlobals[$key] = [
                'set' => array_key_exists($key, $GLOBALS),
                'value' => $GLOBALS[$key] ?? null,
            ];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedGlobals as $key => $saved) {
            if ($saved['set']) {
                $GLOBALS[$key] = $saved['value'];
            } else {
                unset($GLOBALS[$key]);
            }
        }
    }

    public function testIsConfiguredAlwaysTrue(): void
    {
        $config = new GlobalConfig([]);
        $this->assertTrue($config->isConfigured());
    }

    public function testGetGlobalSettingReturnsValueOrNull(): void
    {
        $config = new GlobalConfig(['foo' => 'bar']);
        $this->assertSame('bar', $config->getGlobalSetting('foo'));
        $this->assertNull($config->getGlobalSetting('missing'));
    }

    public function testSmartAppClientIdLookup(): void
    {
        $config = new GlobalConfig([
            GlobalConfig::DC_ASSESSMENTS_CONFIG_CLIENT_ID => 'client-abc',
        ]);
        $this->assertSame('client-abc', $config->getSmartAppClientId());
    }

    public function testFhirPatientClientIdLookup(): void
    {
        $config = new GlobalConfig([
            GlobalConfig::DC_ASSESSMENTS_CONFIG_PATIENT_CLIENT_ID => 'patient-xyz',
        ]);
        $this->assertSame('patient-xyz', $config->getFHIRPatientClientId());
    }

    public function testRootDirAndSiteAddressAndAppNameLookups(): void
    {
        $config = new GlobalConfig([
            'webserver_root' => '/var/www/openemr',
            'qualified_site_addr' => 'https://emr.example.org',
            GlobalConfig::INSTALLATION_NAME => 'Example Clinic',
            'patient_reminder_sender_email' => 'noreply@example.org',
        ]);
        $this->assertSame('/var/www/openemr', $config->getRootDir());
        $this->assertSame('https://emr.example.org', $config->getQualifiedSiteAddress());
        $this->assertSame('Example Clinic', $config->getApplicationName());
        $this->assertSame('noreply@example.org', $config->getNotificationDefaultFrom());
        $this->assertSame('noreply@example.org', $config->getNotificationDefaultReplyTo());
    }

    public function testStaticSmartAppMetadata(): void
    {
        $config = new GlobalConfig([]);
        $this->assertSame('Discover and Change Assessment Platform', $config->getSmartAppName());
        $this->assertSame('info@discoverandchange.com', $config->getSmartAppContactAddress());
    }

    public function testPatientClientScopesIsFixedString(): void
    {
        $config = new GlobalConfig([]);
        $this->assertSame(
            'launch/patient api:fhir openid profile patient/Task.read patient/Questionnaire.read',
            $config->getPatientClientScopes()
        );
    }

    public function testSmartAppScopesContainsExpectedScopes(): void
    {
        $config = new GlobalConfig([]);
        $scopes = $config->getSmartAppScopes();
        $this->assertStringContainsString('openid', $scopes);
        $this->assertStringContainsString('patient/Task.read', $scopes);
        $this->assertStringContainsString('user/assessments.read', $scopes);
    }

    public function testShouldDisplayUpdatedOAuthPages(): void
    {
        $on = new GlobalConfig([
            GlobalConfig::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES => '1',
        ]);
        $this->assertTrue($on->shouldDisplayUpdatedOAuthPages());

        $off = new GlobalConfig([
            GlobalConfig::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES => '0',
        ]);
        $this->assertFalse($off->shouldDisplayUpdatedOAuthPages());

        $absent = new GlobalConfig([]);
        $this->assertFalse($absent->shouldDisplayUpdatedOAuthPages());
    }

    public function testGlobalSettingSectionConfigurationStructure(): void
    {
        $config = new GlobalConfig([]);
        $settings = $config->getGlobalSettingSectionConfiguration();

        $this->assertArrayHasKey(GlobalConfig::DC_ASSESSMENTS_CONFIG_CLIENT_ID, $settings);
        $this->assertArrayHasKey(GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG, $settings);
        $this->assertArrayHasKey(GlobalConfig::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES, $settings);
        $this->assertCount(5, $settings);

        foreach ($settings as $entry) {
            $this->assertArrayHasKey('title', $entry);
            $this->assertArrayHasKey('description', $entry);
            $this->assertArrayHasKey('type', $entry);
            $this->assertArrayHasKey('default', $entry);
        }

        // The OAuth2 override defaults to enabled ('1').
        $this->assertSame(
            '1',
            $settings[GlobalConfig::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES]['default']
        );
    }

    public function testPublicPathBuildingUsesGlobalsAndWebRoot(): void
    {
        $GLOBALS['qualified_site_addr'] = 'https://emr.example.org';
        $config = new GlobalConfig(['web_root' => '/oemr']);

        $base = 'https://emr.example.org/oemr/interface/modules/custom_modules/'
            . 'oe-module-dc-assessments/public/';

        $this->assertSame($base, $config->getPublicPathFQDN());
        $this->assertSame($base . 'backend/', $config->getPublicBackendPathFQDN());
        $this->assertSame($base . 'frontend/', $config->getPublicFrontendPathFQDN());
        $this->assertSame($base . 'index.php', $config->getPatientClientRedirectUrl());
    }

    public function testSmartAppPathsDeriveFromPublicPath(): void
    {
        $GLOBALS['qualified_site_addr'] = 'https://emr.example.org';
        $config = new GlobalConfig(['web_root' => '/oemr']);

        $base = 'https://emr.example.org/oemr/interface/modules/custom_modules/'
            . 'oe-module-dc-assessments/public/';

        $this->assertSame($base . 'frontend/', $config->getSmartAppAdminRootPath());
        $this->assertSame($base . 'frontend/login', $config->getSmartAppAdminLoginPublicPath());
        $this->assertSame($base . 'frontend/loginFinalize', $config->getSmartAppAdminPublicPathRedirectUri());
        $this->assertSame($base . 'frontend/login', $config->getSmartAppClientPublicPath());
        $this->assertSame($base . 'frontend/loginFinalize', $config->getSmartAppClientPublicPathRedirectUri());
        $this->assertSame(
            $base . 'frontend/std/assessments/dashboard',
            $config->getSmartAppPatientLaunchUri()
        );
    }

    public function testGetPortalOnsiteAddressBasepathBranch(): void
    {
        $config = new GlobalConfig([
            'portal_onsite_two_basepath' => '1',
            'qualified_site_addr' => 'https://emr.example.org',
        ]);
        $this->assertSame('https://emr.example.org/portal/patient', $config->getPortalOnsiteAddress());
    }

    public function testGetPortalOnsiteAddressExplicitAddressBranch(): void
    {
        $config = new GlobalConfig([
            'portal_onsite_two_basepath' => '0',
            'portal_onsite_two_address' => 'https://portal.example.org/',
        ]);
        $this->assertSame('https://portal.example.org/', $config->getPortalOnsiteAddress());
    }

    public function testShouldSendProviderNotificationReadsGlobals(): void
    {
        $config = new GlobalConfig([]);

        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG] = '1';
        $this->assertTrue($config->shouldSendProviderNotification());

        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG] = '0';
        $this->assertFalse($config->shouldSendProviderNotification());

        unset($GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG]);
        $this->assertFalse($config->shouldSendProviderNotification());
    }

    public function testGetAssignmentCompletionNoticeUserIdReadsGlobals(): void
    {
        $config = new GlobalConfig([]);

        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID] = '7';
        $this->assertSame('7', $config->getAssignmentCompletionNoticeUserId());

        unset($GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID]);
        $this->assertNull($config->getAssignmentCompletionNoticeUserId());
    }

    public function testShouldSendAssignmentCompletionNoticesRequiresFlagAndRecipient(): void
    {
        $config = new GlobalConfig([]);

        // Notices flag off -> always false regardless of recipient.
        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG] = '0';
        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID] = '7';
        $this->assertFalse($config->shouldSendAssignmentCompletionNotices());

        // Notices flag on + a contact user id -> true.
        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_NOTICES_FLAG] = '1';
        $this->assertTrue($config->shouldSendAssignmentCompletionNotices());

        // Notices flag on but no recipient and provider notices off -> false.
        unset($GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_ADDRESS_BOOK_ID]);
        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG] = '0';
        $this->assertFalse($config->shouldSendAssignmentCompletionNotices());

        // Notices flag on + provider notices on (no contact user) -> true.
        $GLOBALS[GlobalConfig::DC_ASSESSMENTS_CONFIG_COMPLETION_SEND_PROVIDER_NOTICES_FLAG] = '1';
        $this->assertTrue($config->shouldSendAssignmentCompletionNotices());
    }
}
