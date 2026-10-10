<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\BC\ServiceContainer;
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

    /**
     * @return bool
     */
    public function isClientEnabled(string $clientId)
    {
        return $this->clientService->isClientEnabled($clientId);
    }

    /**
     * @param array<mixed> $queryVars
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(array $queryVars)
    {
        /** @var string|null $clientId */
        $clientId = $this->config->getSmartAppClientId();
        if (($clientId === null || $clientId === '') || !$this->isClientEnabled($clientId)) {
            // if the client is not enabled we need to present a message to the user.
            // return the error response (HTTP 500) so the caller can emit it -- previously this die()d,
            // which stopped the request and made the controller untestable.
            $body = $this->twig->render('error/500.html.twig', ['exception' => "The client is not enabled. Please contact your administrator."]);
            return ServiceContainer::getResponseFactory()->createResponse(500)
                ->withBody(ServiceContainer::getStreamFactory()->createStream($body));
        }
        $smartJSON = $this->getSmartStylesJson();
        // the admin/provider app authenticates with the CONFIDENTIAL provider client (user/* scopes),
        // not the public patient client; the SPA uses it for admin routes + the token broker.
        $adminClientId = $this->clientService->getRegisteredProviderClientId();
        $twig = $this->twig;
        $vars = [
            'clientId' => $clientId
            ,'adminClientId' => $adminClientId
            ,'adminScopes' => $this->config->getSmartAppProviderScopes()
            ,'fhirUrl' => $this->config->getFHIRUrl()
            ,'apiUrl' => $this->config->getAPIUrl() . "/"
            ,'baseHref' => $this->config->getPublicFrontendPathFQDN()
            ,'AuthToken' => '' // leave empty for now
            ,'smartStyles' => $smartJSON
        ];
        $result = $twig->render("discoverandchange/frontend/frontend.html.twig", $vars);
        return ServiceContainer::getResponseFactory()->createResponse()->withBody(ServiceContainer::getStreamFactory()->createStream($result));
    }

    /**
     * @return array<mixed>
     */
    private function getSmartStylesJson()
    {
        // TODO: @adunsulag I don't like the duplicate code here and in SMARTAuthorizationController->smartAppStyles()
        // TODO: @adunsulag look at refactoring this to be more DRY
        /** @var string $cssTheme */
        $cssTheme = OEGlobalsBag::getInstance()->getString('css_header');
        $baseNameCssTheme = basename($cssTheme);
        $parts = explode(".", $baseNameCssTheme);
        $coreTheme = $parts[0];
        $logoService = new LogoService();
        // do we want to expose each of the logos?  These really need to be cached instead of hitting FS each time...
        /** @var string $siteAddrOauth */
        $siteAddrOauth = OEGlobalsBag::getInstance()->get('site_addr_oath');
        /** @var string $webRoot */
        $webRoot = OEGlobalsBag::getInstance()->get('web_root');
        $primaryLogo = $siteAddrOauth . $webRoot . $logoService->getLogo("core/login/primary");
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
        if ($stringVar !== '') {
            /** @var array<mixed> $json */
            $json = json_decode($stringVar, true) ?? [];
        }
        return $json;
    }
}
