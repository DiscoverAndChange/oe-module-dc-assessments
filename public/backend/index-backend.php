<?php

// include openemr globals
require_once(__DIR__ . "/../../../../../globals.php");

use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;

// grab our bootstrap class
/**
 * @var OpenEMR\Core\Kernel
 */
$kernel =  $GLOBALS['kernel'];
$bootstrap = Bootstrap::instantiate($kernel->getEventDispatcher(), $kernel);

$queryVars = $_GET;
$action = $_REQUEST['action'] ?? '';
$queryVars = $_REQUEST ?? [];
$queryVars['pid'] = $_REQUEST['pid'] ?? null;
$queryVars['authUser'] = \OpenEMR\Common\Session\SessionWrapperFactory::getInstance()->getActiveSession()->get('authUser');
if (!empty($_SERVER['HTTP_APICSRFTOKEN'])) {
    $queryVars['csrf_token'] = $_SERVER['HTTP_APICSRFTOKEN'];
}

// grab our twig environment
$controller = $bootstrap->getBackendDispatchController();
$response = $controller->dispatch($action, $queryVars);
RestUtils::emitResponse($response);
exit;
