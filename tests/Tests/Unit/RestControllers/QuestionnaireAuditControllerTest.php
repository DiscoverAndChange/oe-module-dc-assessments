<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireAuditController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * Covers QuestionnaireAuditController's dispatch routing and the no-DB guard/error branches:
 * unknown action -> 404, view with a missing recordId or no matching audit -> 404, and an
 * exception bubbling to the dispatch catch -> returnError 500. All seven constructor deps are
 * mocked (Twig::render is stubbed to a sentinel body); the chart/view happy paths render real
 * templates and write to the DB and are left to integration coverage.
 */
class QuestionnaireAuditControllerTest extends TestCase
{
    /**
     * @param AssignmentRepository|null $assignmentRepo pass a pre-configured mock, else a default
     */
    private function makeController(?AssignmentRepository $assignmentRepo = null): QuestionnaireAuditController
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<rendered/>');

        return new QuestionnaireAuditController(
            new SystemLogger(),
            $twig,
            $this->createMock(QuestionnaireService::class),
            $this->createMock(QuestionnaireResponseService::class),
            $assignmentRepo ?? $this->createMock(AssignmentRepository::class),
            $this->createMock(GlobalConfig::class),
            ''
        );
    }

    public function testDispatchUnknownActionReturns404(): void
    {
        $response = $this->makeController()->dispatch('no-such-action', []);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('rendered', (string) $response->getBody());
    }

    public function testViewWithMissingRecordIdReturns404(): void
    {
        // empty recordId short-circuits to actionNotFound before any repo/DB access
        $response = $this->makeController()->dispatch('view', ['recordId' => '', 'pid' => '1']);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testViewWithNoMatchingAuditReturns404(): void
    {
        $repo = $this->createMock(AssignmentRepository::class);
        $repo->method('getAssignmentItemsForAuditId')->willReturn([]);

        $response = $this->makeController($repo)->dispatch('view', ['recordId' => 'phptest-missing', 'pid' => '1']);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDispatchReturns500WhenAnActionThrows(): void
    {
        $repo = $this->createMock(AssignmentRepository::class);
        $repo->method('getAssignmentItemsForAuditId')->willThrowException(new \RuntimeException('boom'));

        $response = $this->makeController($repo)->dispatch('view', ['recordId' => 'phptest-x', 'pid' => '1']);

        $this->assertSame(500, $response->getStatusCode());
    }
}
