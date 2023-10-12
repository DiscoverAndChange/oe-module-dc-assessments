<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use RestConfig;

require_once(__DIR__ . "/../../../globals.php");

// include rest config
require_once(__DIR__ . "/../../../../_rest_config.php");

/**
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */
$bootstrap = Bootstrap::instantiate($GLOBALS['kernel']->getEventDispatcher(), $GLOBALS['kernel']);
$backendController = $bootstrap->getBackendDispatchController();

$action = $_REQUEST['action'] ?? 'config';
$response = $backendController->dispatch($action, $_REQUEST);

RestConfig::emitResponse($response);
exit;
