<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Auth\OpenIDConnect\Entities\ClientEntity;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ClientRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\FrontendDispatchController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Covers FrontendDispatchController::dispatch() after the die()->return refactor.
 *
 * The "client not enabled" branch used to die($body), which (a) made the controller impossible to
 * unit-test and (b) emitted the error page with a 200. It now RETURNS a 500 ResponseInterface so the
 * caller (public/frontend/index.php) emits it like any other response. Reaching the assertions at all
 * proves execution was no longer halted by die().
 *
 * Twig is injected as a self-contained ArrayLoader environment so the tests don't depend on theme
 * globals / core template resolution (which the unit harness does not provide).
 */
class FrontendDispatchControllerTest extends TestCase
{
    private function twig(): Environment
    {
        return new Environment(new ArrayLoader([
            'error/500.html.twig' => 'ERROR:{{ exception }}',
            'discoverandchange/frontend/frontend.html.twig' => 'SPA clientId={{ clientId }}',
            // smart-style default resolved by getSmartStylesJson(); empty JSON is fine
            '/api/smart/smart-style_light.json.twig' => '{}',
        ]));
    }

    private function clientService(bool $enabled): SmartAppClientService
    {
        $repo = new class ($enabled) extends ClientRepository {
            public function __construct(private bool $enabled)
            {
                parent::__construct();
            }
            public function getClientEntity($clientIdentifier): ClientEntity|false
            {
                $entity = new ClientEntity();
                $entity->setIsEnabled($this->enabled);
                return $entity;
            }
        };
        return new SmartAppClientService(new GlobalConfig([]), $repo);
    }

    public function testDispatchReturnsErrorResponseWhenNoClientIdConfigured(): void
    {
        $controller = new FrontendDispatchController(
            new GlobalConfig([]), // no DC_ASSESSMENTS_CONFIG_CLIENT_ID -> getSmartAppClientId() is null
            $this->clientService(true),
            $this->twig(),
            new EventDispatcher()
        );

        $response = $controller->dispatch([]);

        $this->assertSame(500, $response->getStatusCode(), 'an unconfigured client must yield a 500, not a halted request');
        $this->assertStringContainsString('ERROR:', (string) $response->getBody());
    }

    public function testDispatchReturnsErrorResponseWhenClientNotEnabled(): void
    {
        $controller = new FrontendDispatchController(
            new GlobalConfig([GlobalConfig::DC_ASSESSMENTS_CONFIG_CLIENT_ID => 'configured-but-disabled']),
            $this->clientService(false),
            $this->twig(),
            new EventDispatcher()
        );

        $response = $controller->dispatch([]);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('ERROR:', (string) $response->getBody());
    }
}
