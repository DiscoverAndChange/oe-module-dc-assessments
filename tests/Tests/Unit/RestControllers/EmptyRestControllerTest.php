<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\EmptyRestController;
use PHPUnit\Framework\TestCase;

/**
 * EmptyRestController is the placeholder backing the announcements route: every action
 * returns a 200 with an empty JSON array. ServerRestRequest is final, so a real instance
 * wrapping a mocked HttpRestRequest is used.
 */
class EmptyRestControllerTest extends TestCase
{
    private function request(): ServerRestRequest
    {
        return new ServerRestRequest($this->createMock(HttpRestRequest::class));
    }

    public function testListReturnsEmptyArray(): void
    {
        $response = (new EmptyRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    public function testOneReturnsEmptyArray(): void
    {
        $response = (new EmptyRestController())->one($this->request(), 'x');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    public function testCreateReturnsEmptyArray(): void
    {
        $response = (new EmptyRestController())->create($this->request());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateReturnsEmptyArray(): void
    {
        $response = (new EmptyRestController())->update($this->request(), 'x');
        $this->assertSame(200, $response->getStatusCode());
    }
}
