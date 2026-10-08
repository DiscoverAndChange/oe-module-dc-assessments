<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\RestControllers;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TokenRestController;
use PHPUnit\Framework\TestCase;

/**
 * TokenRestController::list delegates to TokenRepository (a stub returning []), so list
 * returns a 200 with an empty array; one() is 404, writes are 400.
 */
class TokenRestControllerTest extends TestCase
{
    private function request(): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getRequestUserId')->willReturn(1);
        return new ServerRestRequest($inner);
    }

    public function testListReturns200EmptyArray(): void
    {
        $response = (new TokenRestController())->list($this->request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    public function testOneReturns404(): void
    {
        $this->assertSame(404, (new TokenRestController())->one($this->request(), 'x')->getStatusCode());
    }

    public function testCreateReturns400(): void
    {
        $this->assertSame(400, (new TokenRestController())->create($this->request())->getStatusCode());
    }

    public function testUpdateReturns400(): void
    {
        $this->assertSame(400, (new TokenRestController())->update($this->request(), 'x')->getStatusCode());
    }
}
