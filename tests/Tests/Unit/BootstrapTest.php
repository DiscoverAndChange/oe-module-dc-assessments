<?php

/**
 * BootstrapTest.php
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Events\Core\ScriptFilterEvent;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\BackendDispatchController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireAuditController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Smoke tests for the module Bootstrap: constructing it compiles the Symfony DI container
 * (setupContainer), and the public accessors resolve real services out of it, exercising
 * the service-definition wiring. Constructing directly (not via instantiate()) avoids the
 * singleton and the event-subscription side effects, except where a test opts into them.
 */
class BootstrapTest extends TestCase
{
    public function testConstructBuildsServiceContainer(): void
    {
        $bootstrap = new Bootstrap(new EventDispatcher());
        $this->assertNotNull($bootstrap->getServiceContainer());
    }

    /**
     * The two public controllers pull Twig transitively, and instantiating Twig needs theme
     * globals not present in the unit harness, so assert the compiled container has the
     * services WIRED (definitions registered) rather than instantiating them here.
     */
    public function testContainerWiresPublicControllers(): void
    {
        $container = (new Bootstrap(new EventDispatcher()))->getServiceContainer();

        $this->assertTrue($container->has(QuestionnaireAuditController::class));
        $this->assertTrue($container->has(BackendDispatchController::class));
    }

    public function testSubscribeToEventsRegistersListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $bootstrap = new Bootstrap($dispatcher);

        $bootstrap->subscribeToEvents();

        // a representative sample of the listeners wired in subscribeToEvents()
        $this->assertTrue($dispatcher->hasListeners(ScriptFilterEvent::EVENT_NAME));
        $this->assertTrue($dispatcher->hasListeners(TwigEnvironmentEvent::EVENT_CREATED));
    }
}
