<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use OpenEMR\Common\Http\Psr17Factory;
use OpenEMR\Events\Core\TemplatePageEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;
use OpenEMR\Services\LogoService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;

class FrontendDispatchController
{
    public function __construct(private GlobalConfig $config, private SmartAppClientService $clientService, private Environment $twig, private EventDispatcher $dispatcher)
    {
    }

    public function isClientEnabled(string $clientId)
    {
        return $this->clientService->isClientEnabled($clientId);
    }

    public function dispatch(array $queryVars)
    {
        $clientId = $this->config->getSmartAppClientId();
        if (empty($clientId) || !$this->isClientEnabled($clientId)) {
            // if the client is not enabled we need to present a message to the user
            $body = $this->twig->render('error/500.html.twig', ['exception' => "The client is not enabled. Please contact your administrator."]);
            die($body);
        }
        $smartJSON = $this->getSmartStylesJson();
        $twig = $this->twig;
        $vars = [
            'clientId' => $clientId
            ,'fhirUrl' => $this->config->getFHIRUrl()
            ,'apiUrl' => $this->config->getAPIUrl() . "/"
            ,'baseHref' => $this->config->getPublicFrontendPathFQDN()
            ,'AuthToken' => '' // leave empty for now
            ,'smartStyles' => $smartJSON
        ];
        $result = $twig->render("discoverandchange/frontend/frontend.html.twig", $vars);
        $psr = new Psr17Factory();
        return $psr->createResponse()->withBody($psr->createStream($result));
    }

    private function getSmartStylesJson()
    {
        // TODO: @adunsulag I don't like the duplicate code here and in SMARTAuthorizationController->smartAppStyles()
        // TODO: @adunsulag look at refactoring this to be more DRY
        $cssTheme = $GLOBALS['css_header'];
        $baseNameCssTheme = basename($cssTheme);
        $parts = explode(".", $baseNameCssTheme);
        $coreTheme = $parts[0] ?? "style_light";
        $logoService = new LogoService();
        // do we want to expose each of the logos?  These really need to be cached instead of hitting FS each time...
        $primaryLogo = $GLOBALS['site_addr_oath'] . $GLOBALS['web_root'] . $logoService->getLogo("core/login/primary");
        $context = [
            'logo' => [
                'primary' => $primaryLogo
            ]
        ];
        $defaultFile = "/api/smart/smart-style_light.json.twig";
        $themeFile = "/api/smart/smart-" . $coreTheme . ".json.twig";
        $templatePageEvent = new TemplatePageEvent('oauth2/authorize/smart-style', [], $themeFile, $context);
        $updatedTemplatePageEvent = $this->dispatcher->dispatch($templatePageEvent);
        $template = $updatedTemplatePageEvent->getTwigTemplate();
        $vars = $updatedTemplatePageEvent->getTwigVariables();

        $templates = [$template, $defaultFile];
        $resolvedTemplate = $this->twig->resolveTemplate($templates);
        $stringVar = $resolvedTemplate->render($vars);
        $json = [];
        if (!empty($stringVar)) {
            $json = json_decode($stringVar, true) ?? [];
        }
        return $json;
    }
}
