<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;

require_once(__DIR__ . "/../../../globals.php");

/**
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */
$bootstrap = Bootstrap::instantiate($GLOBALS['kernel']->getEventDispatcher(), $GLOBALS['kernel']);
$backendController = $bootstrap->getBackendDispatchController();

$action = $_REQUEST['action'] ?? 'config';
$response = $backendController->dispatch($action, $_REQUEST);

RestUtils::emitResponse($response);
exit;
