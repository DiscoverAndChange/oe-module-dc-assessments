<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Factory;

use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SimplifiedOAuthTwigExtension;
use Twig\Environment;

class TwigEnvironmentFactory
{
    public function __construct(private SimplifiedOAuthTwigExtension $oauthTwigExtension, private Kernel $kernel, private string $templatePath)
    {
    }

    public function __invoke(): Environment
    {
        $twigContainer = new TwigContainer(null, $this->kernel);
        // note that TwigContainer fires off the TwigEnvironmentEvent::EVENT_CREATED which will add in our extension
        // and path template here
        $twigEnv = $twigContainer->getTwig();
        return $twigEnv;
    }
}
