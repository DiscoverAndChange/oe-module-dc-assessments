<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Events\Core\TemplatePageEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Covers Bootstrap::oauth2TemplatePageOverrides() -- the hook that swaps OpenEMR's core OAuth2
 * pages (the login screen the patient SMART flow lands on, plus scope-authorize / patient-select)
 * for the module's own twigs. These overrides are gated on the dac_assessments_oauth2_layout_override
 * global, so the routing is: flag on + matching page -> module twig; flag off -> untouched; and the
 * core error page is never overridden.
 */
class BootstrapOAuth2OverrideTest extends TestCase
{
    private const FLAG = GlobalConfig::DC_ASSESSMENTS_CONFIG_SHOW_UPDATED_OAUTH2_PAGES;

    /** @var array{set: bool, value: mixed} */
    private array $savedFlag;

    protected function setUp(): void
    {
        $this->savedFlag = ['set' => array_key_exists(self::FLAG, $GLOBALS), 'value' => $GLOBALS[self::FLAG] ?? null];
    }

    protected function tearDown(): void
    {
        if ($this->savedFlag['set']) {
            $GLOBALS[self::FLAG] = $this->savedFlag['value'];
        } else {
            unset($GLOBALS[self::FLAG]);
        }
    }

    private function bootstrapWithFlag(string $flagValue): Bootstrap
    {
        // Bootstrap's constructor snapshots $GLOBALS into its GlobalConfig, so set the flag first.
        $GLOBALS[self::FLAG] = $flagValue;
        return new Bootstrap(new EventDispatcher());
    }

    public function testLoginPageOverriddenWhenFlagEnabled(): void
    {
        $bootstrap = $this->bootstrapWithFlag('1');
        $event = new TemplatePageEvent('oauth2/authorize/login');

        $result = $bootstrap->oauth2TemplatePageOverrides($event);

        $this->assertSame('discoverandchange/oauth2/oauth2-login.html.twig', $result->getTwigTemplate());
    }

    public function testPatientSelectPageOverriddenWhenFlagEnabled(): void
    {
        $bootstrap = $this->bootstrapWithFlag('1');
        $event = new TemplatePageEvent('oauth2/authorize/patient-select');

        $result = $bootstrap->oauth2TemplatePageOverrides($event);

        $this->assertSame('discoverandchange/oauth2/patient-select.html.twig', $result->getTwigTemplate());
    }

    public function testLoginPageNotOverriddenWhenFlagDisabled(): void
    {
        $bootstrap = $this->bootstrapWithFlag('0');
        $event = new TemplatePageEvent('oauth2/authorize/login');

        $result = $bootstrap->oauth2TemplatePageOverrides($event);

        $this->assertSame('', $result->getTwigTemplate(), 'with the flag off the core template is left untouched');
    }

    /** The core http-error page must never be swapped for the module login, even on the login page. */
    public function testErrorTemplateIsNotOverridden(): void
    {
        $bootstrap = $this->bootstrapWithFlag('1');
        $event = new TemplatePageEvent('oauth2/authorize/login', [], 'error/general_http_error.html.twig');

        $result = $bootstrap->oauth2TemplatePageOverrides($event);

        $this->assertSame('error/general_http_error.html.twig', $result->getTwigTemplate());
    }
}
