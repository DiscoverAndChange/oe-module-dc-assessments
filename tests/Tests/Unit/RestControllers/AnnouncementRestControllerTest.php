<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AnnouncementRestController;
use PHPUnit\Framework\TestCase;

/**
 * AnnouncementRestController is a placeholder: reads return 200 with an empty array,
 * writes return 400 (not implemented).
 */
class AnnouncementRestControllerTest extends TestCase
{
    private function request(): ServerRestRequest
    {
        return new ServerRestRequest($this->createMock(HttpRestRequest::class));
    }

    public function testListReturns200EmptyArray(): void
    {
        $response = (new AnnouncementRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    public function testOneReturns200EmptyArray(): void
    {
        $response = (new AnnouncementRestController())->one($this->request(), 'x');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreateReturns400(): void
    {
        $this->assertSame(400, (new AnnouncementRestController())->create($this->request())->getStatusCode());
    }

    public function testUpdateReturns400(): void
    {
        $this->assertSame(400, (new AnnouncementRestController())->update($this->request(), 'x')->getStatusCode());
    }
}
