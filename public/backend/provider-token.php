<?php

/**
 * Server-side provider token broker (backend-for-frontend).
 *
 * The provider/admin SMART client is CONFIDENTIAL (OpenEMR only grants user/* scopes to confidential
 * clients), so its authorization_code -> token exchange needs the client_secret, which must never ship
 * to the browser. The admin SPA runs the PKCE authorize itself and then POSTs {code, code_verifier,
 * redirect_uri} here; this endpoint adds the server-side client_id + client_secret and returns the
 * token response. $ignoreAuth is true because the single-use, redirect-bound authorization code is the
 * proof of the completed authorize -- the secret is what this endpoint contributes.
 */

$ignoreAuth = true;
$sessionAllowWrite = false;
require_once(__DIR__ . "/../../../../../globals.php");

use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;

header('Content-Type: application/json');

try {
    $raw = (string) file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $code = (string) ($input['code'] ?? '');
    $verifier = (string) ($input['code_verifier'] ?? '');
    $redirect = (string) ($input['redirect_uri'] ?? '');
    if ($code === '' || $verifier === '' || $redirect === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters']);
        exit;
    }

    /** @var \OpenEMR\Core\Kernel $kernel */
    $kernel = $GLOBALS['kernel'];
    $bootstrap = Bootstrap::instantiate($kernel->getEventDispatcher(), $kernel);
    /** @var SmartAppClientService $svc */
    $svc = $bootstrap->getServiceContainer()->get(SmartAppClientService::class);
    $result = $svc->exchangeProviderAuthorizationCode($code, $verifier, $redirect);

    if (isset($result['error'])) {
        http_response_code(400);
    }
    echo json_encode($result);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'exchange_failed', 'message' => $e->getMessage()]);
}
