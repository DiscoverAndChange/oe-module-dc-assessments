<?php

// include openemr globals
require_once(__DIR__ . "/../../../../../globals.php");

use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

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
$queryVars['authUser'] = $_SESSION['authUser'] ?? null;
if (!empty($_SERVER['HTTP_APICSRFTOKEN'])) {
    $queryVars['csrf_token'] = $_SERVER['HTTP_APICSRFTOKEN'];
}

// grab our twig environment
$controller = $bootstrap->getQuestionnaireAuditController();
$response = $controller->dispatch($action, $queryVars);
$httpFoundationFactory = new HttpFoundationFactory();

// convert a Response
// $psrResponse is an instance of Psr\Http\Message\ResponseInterface
$symfonyResponse = $httpFoundationFactory->createResponse($response);
$symfonyResponse->send();
exit;
