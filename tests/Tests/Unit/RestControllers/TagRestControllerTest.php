<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TagRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TagRepository;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed test for TagRestController (list delegates to TagRepository::listTags against
 * the oe-test-db harness). Seeded tags use a 'phptest-' prefix, cleaned up in tearDown.
 */
class TagRestControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . TagRepository::TABLE_NAME . " WHERE tag LIKE 'phptest-%'",
            [],
            true
        );
    }

    private function request(): ServerRestRequest
    {
        return new ServerRestRequest($this->createMock(HttpRestRequest::class));
    }

    public function testListReturnsSeededTags(): void
    {
        QueryUtils::sqlStatementThrowException("INSERT INTO " . TagRepository::TABLE_NAME . " (tag) VALUES (?)", ['phptest-ctrl-tag']);

        $response = (new TagRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());

        /** @var array<int,string> $body */
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertContains('phptest-ctrl-tag', $body);
    }

    public function testOneReturns404(): void
    {
        $this->assertSame(404, (new TagRestController())->one($this->request(), 'x')->getStatusCode());
    }

    public function testCreateReturns400(): void
    {
        $this->assertSame(400, (new TagRestController())->create($this->request())->getStatusCode());
    }
}
