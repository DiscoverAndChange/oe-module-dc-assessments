<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use RestConfig;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

require_once(__DIR__ . "/../../../globals.php");

/**
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */
$bootstrap = Bootstrap::instantiate($GLOBALS['kernel']->getEventDispatcher(), $GLOBALS['kernel']);
$backendController = $bootstrap->getBackendDispatchController();

$action = $_REQUEST['action'] ?? 'config';
$response = $backendController->dispatch($action, $_REQUEST);

$httpFoundationFactory = new HttpFoundationFactory();

// convert a Response
// $psrResponse is an instance of Psr\Http\Message\ResponseInterface
$symfonyResponse = $httpFoundationFactory->createResponse($response);
$symfonyResponse->send();
exit;
