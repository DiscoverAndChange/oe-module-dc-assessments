<?php

/**
 * One-shot provisioner for the browser UAT tier (mirrors oe-module-ihi's tools/provision-docker.php).
 *
 * Run INSIDE the OpenEMR container as the web user, e.g.:
 *   docker exec development-easy-openemr-1 \
 *     php interface/modules/custom_modules/oe-module-dc-assessments/tests/Uat/Browser/tools/provision-stack.php
 *
 * It is idempotent and does exactly what the "Manage Modules" admin page would do for this module:
 *   1. registers the module in `modules` (mod_active = 1) + module_acl_sections,
 *   2. runs table.sql through core's SQLUpgradeService (so #IfNotRow2D etc. are honoured -- this is
 *      what sets portal_force_credential_reset = '1'),
 *   3. instantiates the module Bootstrap and calls getClientId(), which registers + enables the
 *      SMART client the patient SPA is gated on.
 *
 * Prints a PROVISION_OK / PROVISION_FAIL line the caller can grep.
 */

declare(strict_types=1);

$GLOBALS['ignoreAuth'] = true;
$ignoreAuth = true;
$sessionAllowWrite = true;

// CLI context: globals.php needs a site id + host or it rejects the request as an invalid URL.
$_GET['site'] = $_GET['site'] ?? 'default';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';

// Walk up from this file until we find the OpenEMR webroot (interface/globals.php).
$dir = __DIR__;
$globals = null;
for ($i = 0; $i < 12; $i++) {
    $candidate = $dir . '/interface/globals.php';
    if (is_file($candidate)) {
        $globals = $candidate;
        break;
    }
    $dir = dirname($dir);
}
if ($globals === null) {
    fwrite(STDERR, "PROVISION_FAIL could not locate interface/globals.php above " . __DIR__ . "\n");
    exit(1);
}
require_once $globals;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Services\Utils\SQLUpgradeService;

try {
    $directory = 'oe-module-dc-assessments';
    $modRoot = $GLOBALS['fileroot'] . "/interface/modules/custom_modules/{$directory}";

    // the module is not active yet, so OpenEMR has not registered its autoloader -- load it so the
    // Bootstrap class (step 3) resolves.
    if (is_file($modRoot . '/vendor/autoload.php')) {
        require_once $modRoot . '/vendor/autoload.php';
    }

    // 1. register the module row (active) if not already present
    $lines = is_file($modRoot . '/info.txt') ? @file($modRoot . '/info.txt') : [];
    $name = !empty($lines) ? trim((string) $lines[0]) : $directory;
    $existing = sqlQuery("SELECT mod_id, mod_active FROM modules WHERE mod_directory = ?", [$directory]);
    if (empty($existing)) {
        $max = sqlQuery("SELECT COALESCE(MAX(section_id), 0) AS m FROM module_acl_sections");
        $sectionId = ((int) ($max['m'] ?? 0)) + 1;
        $uiname = ucwords(strtolower($directory));
        $modId = (int) sqlInsert(
            // type = 0 (MODULE_TYPE_CUSTOM): a custom/SMART module, NOT a Laminas (type 1) module --
            // otherwise OpenEMR's ModulesApplication tries to load it via the Laminas ModuleManager.
            "INSERT INTO modules SET mod_id = ?, mod_name = ?, mod_active = 1, mod_ui_name = ?, "
            . "mod_relative_link = ?, mod_directory = ?, type = 0, date = NOW()",
            [$sectionId, $name, $uiname, strtolower($directory), $directory]
        );
        fwrite(STDERR, "[provision] registered module row mod_id={$modId}\n");
    } else {
        $modId = (int) $existing['mod_id'];
        if ((int) $existing['mod_active'] !== 1) {
            sqlStatement("UPDATE modules SET mod_active = 1 WHERE mod_directory = ?", [$directory]);
        }
        fwrite(STDERR, "[provision] module already registered (mod_id={$modId}); ensured active\n");
    }

    // ensure the ACL section row exists (idempotent; column is parent_section)
    $haveAcl = sqlQuery("SELECT section_id FROM module_acl_sections WHERE module_id = ?", [$modId]);
    if (empty($haveAcl)) {
        sqlStatement(
            "INSERT INTO module_acl_sections (section_id, section_name, parent_section, section_identifier, module_id) "
            . "VALUES (?, ?, 0, ?, ?)",
            [$modId, $name, strtolower($directory), $modId]
        );
        fwrite(STDERR, "[provision] added module_acl_sections row\n");
    }

    // 2. run table.sql via the upgrade service (handles #IfNotRow2D; sets portal_force_credential_reset)
    if (is_file($modRoot . '/table.sql')) {
        $svc = new SQLUpgradeService();
        $svc->setThrowExceptionOnError(true);
        $svc->setRenderOutputToScreen(false);
        $svc->upgradeFromSqlFile('table.sql', $modRoot);
        fwrite(STDERR, "[provision] table.sql applied\n");
    }

    // 2b. UAT stack prerequisites the patient SMART flow needs (a fresh install does not enable
    //     these; the real deployment already has them). Idempotent upserts into globals.
    $baseUrl = '';
    foreach ($argv as $arg) {
        if (str_starts_with((string) $arg, 'baseurl=')) {
            $baseUrl = rtrim(substr((string) $arg, strlen('baseurl=')), '/');
        }
    }
    $stackGlobals = [
        'rest_api' => '1', 'rest_fhir_api' => '1', 'rest_portal_api' => '1',
        'rest_system_scopes_api' => '1', 'oauth_password_grant' => '3',
        'portal_onsite_two_enable' => '1',
        // the SMART login form has no email field flow in this app, so don't enforce it
        'enforce_signin_email' => '0',
    ];
    if ($baseUrl !== '') {
        $stackGlobals['site_addr_oath'] = $baseUrl;
    }
    foreach ($stackGlobals as $gl => $val) {
        sqlStatement(
            "INSERT INTO globals (gl_name, gl_index, gl_value) VALUES (?, 0, ?) "
            . "ON DUPLICATE KEY UPDATE gl_value = VALUES(gl_value)",
            [$gl, $val]
        );
    }
    fwrite(STDERR, "[provision] stack API/oauth globals ensured" . ($baseUrl !== '' ? " (site_addr_oath={$baseUrl})" : "") . "\n");

    // 3. register + enable BOTH SMART clients: the public PATIENT client (patient SPA) and the
    //    CONFIDENTIAL PROVIDER client (in-EHR provider app, user/* scopes).
    $kernel = $GLOBALS['kernel'] ?? null;
    $bootstrap = Bootstrap::instantiate($kernel->getEventDispatcher(), $kernel);
    $clientId = $bootstrap->getClientId(); // patient client
    /** @var \OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService $smartService */
    $smartService = $bootstrap->getServiceContainer()->get(\OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService::class);
    $providerClientId = $smartService->getRegisteredProviderClientId();
    fwrite(STDERR, "[provision] patient client id = " . var_export($clientId, true) . "\n");
    fwrite(STDERR, "[provision] provider client id = " . var_export($providerClientId, true) . "\n");

    // 3b. Clients are registered from this CLI context with no public port, so their redirect_uri
    //     origin won't match the browser's (e.g. https://localhost vs https://localhost:9302).
    //     Rewrite each redirect_uri's scheme+host to the public base URL so OAuth2 authorize doesn't
    //     fail with invalid_client. Applies to both clients.
    if ($baseUrl !== '') {
        foreach (array_filter([$clientId, $providerClientId], 'is_string') as $cid) {
            if ($cid === '') {
                continue;
            }
            $row = sqlQuery("SELECT redirect_uri FROM oauth_clients WHERE client_id = ?", [$cid]);
            if (!empty($row['redirect_uri'])) {
                $fixed = implode('|', array_map(
                    static function (string $uri) use ($baseUrl): string {
                        $path = (string) parse_url(trim($uri), PHP_URL_PATH);
                        return $path !== '' ? $baseUrl . $path : trim($uri);
                    },
                    explode('|', (string) $row['redirect_uri'])
                ));
                sqlStatement("UPDATE oauth_clients SET redirect_uri = ? WHERE client_id = ?", [$fixed, $cid]);
                fwrite(STDERR, "[provision] {$cid} redirect_uri set to {$fixed}\n");
            }
        }
    }

    // report the two things the UAT preflight / login depend on
    $reset = sqlQuery("SELECT gl_value FROM globals WHERE gl_name = 'portal_force_credential_reset'");
    $active = sqlQuery("SELECT mod_active FROM modules WHERE mod_directory = ?", [$directory]);
    fwrite(
        STDOUT,
        "PROVISION_OK mod_active=" . ($active['mod_active'] ?? '?')
        . " portal_force_credential_reset=" . ($reset['gl_value'] ?? 'unset')
        . " patient_client=" . (is_string($clientId) && $clientId !== '' ? 'registered' : 'MISSING')
        . " provider_client=" . (is_string($providerClientId) && $providerClientId !== '' ? 'registered' : 'MISSING')
        . "\n"
    );
} catch (\Throwable $e) {
    fwrite(STDERR, "PROVISION_FAIL " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
