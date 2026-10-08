<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\MessageTemplateRestController;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * DB-backed test for MessageTemplateRestController::list against the oe-test-db harness.
 *
 * list() resolves the message template for the patient identified by the `clientId` query
 * param. The first thing it does is look that patient up via PatientService::getOne; when the
 * client id is not an existing patient uuid it short-circuits to a 404 before touching the
 * Twig environment or GlobalConfig. That not-found branch is the deterministic READ-path
 * assertion here.
 *
 * The happy path (a real seeded patient + user + rendered Twig template) is NOT covered: it
 * requires a fully seeded patient/user chain and a rendered template body, which cannot be
 * exercised against the shared harness without a live Twig render; see TEST-PLAN.md.
 */
class MessageTemplateRestControllerTest extends TestCase
{
    private function controller(): MessageTemplateRestController
    {
        return new MessageTemplateRestController(
            new SystemLogger(),
            $this->createMock(Environment::class),
            $this->createMock(GlobalConfig::class)
        );
    }

    private function requestWithClientId(string $clientId): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getQueryParams')->willReturn(['clientId' => $clientId]);
        return new ServerRestRequest($inner);
    }

    public function testListReturns404ForUnknownClient(): void
    {
        $response = $this->controller()->list($this->requestWithClientId('phptest-no-such-patient-uuid'));
        $this->assertSame(404, $response->getStatusCode());
    }
}
