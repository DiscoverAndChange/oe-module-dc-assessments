<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Factory;

use OpenEMR\BC\ServiceContainer;
use Twig\Environment;

class TwigEnvironmentFactory
{
    public function __invoke(): Environment
    {
        // Use core's service accessor rather than instantiating TwigContainer directly
        // (openemr.forbiddenInstantiation). ServiceContainer::getTwig() builds the Twig environment
        // the same way (new TwigContainer(null, kernel))->getTwig()), which fires
        // TwigEnvironmentEvent::EVENT_CREATED -- the module's Bootstrap::addTemplateOverrideLoader
        // listener (registered at bootstrap) then adds the module's SimplifiedOAuthTwigExtension and
        // prepends the module template path, exactly as before.
        return ServiceContainer::getTwig();
    }
}
