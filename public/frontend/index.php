<?php

//
//// instantiate globals
//
//// make sure we can only access the application if we are logged in.
//$sessionAllowWrite = true; //  need to be able to update the session as part of this request.

$ignoreAuth = true;
require_once "../../../../../globals.php";

use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\FrontendDispatchController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;

try {
    /**
     * @var OpenEMR\Core\Kernel
     */
    $kernel =  $GLOBALS['kernel'];
    $bootstrap = Bootstrap::instantiate($kernel->getEventDispatcher(), $kernel);
    $frontendController = $bootstrap->getServiceContainer()->get(FrontendDispatchController::class);
    if ($frontendController instanceof FrontendDispatchController) {
        $response = $frontendController->dispatch($_GET);
        RestUtils::emitResponse($response);
    }
} catch (\Exception $exception) {
    (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
    $twig = (new TwigContainer(null, $GLOBALS['kernel']))->getTwig();
    $body = $twig->render('error/general_http_error.html.twig');
    die($body);
}
