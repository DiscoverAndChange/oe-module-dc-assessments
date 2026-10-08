<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Pure-unit (no DB) characterization tests for ServerRestRequest, the PSR-7
 * wrapper around OpenEMR's HttpRestRequest.
 *
 * The underlying HttpRestRequest is supplied as a PHPUnit mock so no real HTTP
 * request / DB / session is required. Delegation, the immutable wither pattern,
 * the query-param scrubbing and JSON-body decoding are covered here. Branches of
 * getAuthRole() that reach AclMain::aclCheckCore() are deferred (static ACL /
 * session dependency) — only the patient-request branch is exercised.
 */
class ServerRestRequestTest extends TestCase
{
    private function makeRequest(): HttpRestRequest
    {
        return $this->createMock(HttpRestRequest::class);
    }

    public function testGetAuthRoleReturnsClientForPatientRequest(): void
    {
        $inner = $this->makeRequest();
        $inner->method('isPatientRequest')->willReturn(true);

        $request = new ServerRestRequest($inner);
        // Patient branch short-circuits before AclMain is consulted.
        $this->assertSame(Role::Client, $request->getAuthRole());
    }

    public function testIsPatientRequestDelegates(): void
    {
        $inner = $this->makeRequest();
        $inner->method('isPatientRequest')->willReturn(false);

        $request = new ServerRestRequest($inner);
        $this->assertFalse($request->isPatientRequest());
    }

    public function testGetPatientUUIDStringDelegates(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getPatientUUIDString')->willReturn('uuid-123');

        $request = new ServerRestRequest($inner);
        $this->assertSame('uuid-123', $request->getPatientUUIDString());
    }

    public function testGetProtocolVersionDelegates(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getProtocolVersion')->willReturn('1.1');

        $request = new ServerRestRequest($inner);
        $this->assertSame('1.1', $request->getProtocolVersion());
    }

    public function testHeaderAccessorsDelegate(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getHeaders')->willReturn(['X-Test' => ['a', 'b']]);
        $inner->method('hasHeader')->willReturn(true);
        $inner->method('getHeader')->willReturn(['a', 'b']);
        $inner->method('getHeaderLine')->willReturn('a, b');

        $request = new ServerRestRequest($inner);
        $this->assertSame(['X-Test' => ['a', 'b']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('X-Test'));
        $this->assertSame(['a', 'b'], $request->getHeader('X-Test'));
        $this->assertSame('a, b', $request->getHeaderLine('X-Test'));
    }

    public function testGetMethodDelegates(): void
    {
        // NOTE: getRequestTarget()/withRequestTarget() delegate to the wrapped request but
        // are not declared on HttpRestRequest, so they cannot be stubbed on its mock.
        $inner = $this->makeRequest();
        $inner->method('getMethod')->willReturn('POST');

        $request = new ServerRestRequest($inner);
        $this->assertSame('POST', $request->getMethod());
    }

    /**
     * REGRESSION (fixed v0.12.4): HttpRestRequest::getUri() returns a raw string, but
     * ServerRestRequest::getUri() declares UriInterface. It previously returned the string
     * directly and TypeErrored whenever called (e.g. SystemUserRestController::list does
     * $request->getUri()->getQuery()). It now wraps the string in a PSR-7 Uri.
     */
    public function testGetUriWrapsStringInUriInterface(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getUri')->willReturn('/api/v1/assessment-users?_limit=10&_offset=20');

        $uri = (new ServerRestRequest($inner))->getUri();

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('/api/v1/assessment-users', $uri->getPath());
        $this->assertSame('_limit=10&_offset=20', $uri->getQuery());
    }

    public function testGetBodyDelegates(): void
    {
        $inner = $this->makeRequest();
        $body = $this->createMock(StreamInterface::class);
        $inner->method('getBody')->willReturn($body);

        $request = new ServerRestRequest($inner);
        $this->assertSame($body, $request->getBody());
    }

    public function testGetUserIdDelegatesToRequestUserId(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getRequestUserId')->willReturn(42);

        $request = new ServerRestRequest($inner);
        $this->assertSame(42, $request->getUserId());
    }

    public function testGetHttpRestRequestReturnsWrappedInstance(): void
    {
        $inner = $this->makeRequest();
        $request = new ServerRestRequest($inner);
        $this->assertSame($inner, $request->getHttpRestRequest());
    }

    public function testGetCompanyIdReturnsHardcodedOne(): void
    {
        $request = new ServerRestRequest($this->makeRequest());
        $this->assertSame(1, $request->getCompanyId());
    }

    public function testGetQueryParamsStripsXdebugSession(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getQueryParams')->willReturn([
            'foo' => 'bar',
            'XDEBUG_SESSION' => 'phpstorm',
        ]);

        $request = new ServerRestRequest($inner);
        $this->assertSame(['foo' => 'bar'], $request->getQueryParams());
    }

    public function testGetQueryParamsLeavesParamsUntouchedWhenNoDebugKey(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getQueryParams')->willReturn(['foo' => 'bar']);

        $request = new ServerRestRequest($inner);
        $this->assertSame(['foo' => 'bar'], $request->getQueryParams());
    }

    public function testServerCookieAttributeAndUploadedFilesDelegate(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getServerParams')->willReturn(['REQUEST_METHOD' => 'GET']);
        $inner->method('getCookieParams')->willReturn(['sid' => 'x']);
        $inner->method('getUploadedFiles')->willReturn([]);
        $inner->method('getParsedBody')->willReturn(['a' => 1]);
        $inner->method('getAttributes')->willReturn(['attr' => 'v']);
        $inner->method('getAttribute')->with('attr', null)->willReturn('v');

        $request = new ServerRestRequest($inner);
        $this->assertSame(['REQUEST_METHOD' => 'GET'], $request->getServerParams());
        $this->assertSame(['sid' => 'x'], $request->getCookieParams());
        $this->assertSame([], $request->getUploadedFiles());
        $this->assertSame(['a' => 1], $request->getParsedBody());
        $this->assertSame(['attr' => 'v'], $request->getAttributes());
        $this->assertSame('v', $request->getAttribute('attr'));
    }

    public function testGetBodyAsJsonReturnsNullForEmptyContent(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getContent')->willReturn('');

        $request = new ServerRestRequest($inner);
        $this->assertNull($request->getBodyAsJson());
    }

    public function testGetBodyAsJsonDecodesPlainJsonBody(): void
    {
        $inner = $this->makeRequest();
        $inner->method('getContent')->willReturn('{"name":"bob","n":3}');
        // No Content-Encoding header -> plain decode path.
        $inner->headers = new HeaderBag([]);

        $request = new ServerRestRequest($inner);
        $this->assertSame(['name' => 'bob', 'n' => 3], $request->getBodyAsJson());
    }

    public function testGetBodyAsJsonDecodesGzipEncodedBody(): void
    {
        $inner = $this->makeRequest();
        $json = '{"gz":true}';
        $inner->method('getContent')->willReturn(gzencode($json));
        $inner->headers = new HeaderBag(['Content-Encoding' => 'gzip']);

        $request = new ServerRestRequest($inner);
        $this->assertSame(['gz' => true], $request->getBodyAsJson());
    }

    public function testWithersReturnNewWrapperInstances(): void
    {
        $inner = $this->makeRequest();
        // Each wither delegates to the matching HttpRestRequest method and wraps
        // the returned request in a fresh ServerRestRequest (immutability).
        $inner->method('withProtocolVersion')->willReturn($this->makeRequest());
        $inner->method('withHeader')->willReturn($this->makeRequest());
        $inner->method('withAddedHeader')->willReturn($this->makeRequest());
        $inner->method('withoutHeader')->willReturn($this->makeRequest());
        $inner->method('withMethod')->willReturn($this->makeRequest());
        $inner->method('withAttribute')->willReturn($this->makeRequest());
        $inner->method('withoutAttribute')->willReturn($this->makeRequest());
        $inner->method('withQueryParams')->willReturn($this->makeRequest());
        $inner->method('withCookieParams')->willReturn($this->makeRequest());
        $inner->method('withUploadedFiles')->willReturn($this->makeRequest());
        $inner->method('withParsedBody')->willReturn($this->makeRequest());

        $request = new ServerRestRequest($inner);

        $withers = [
            $request->withProtocolVersion('2.0'),
            $request->withHeader('X', 'y'),
            $request->withAddedHeader('X', 'y'),
            $request->withoutHeader('X'),
            $request->withMethod('PUT'),
            $request->withAttribute('k', 'v'),
            $request->withoutAttribute('k'),
            $request->withQueryParams(['a' => 1]),
            $request->withCookieParams(['c' => 1]),
            $request->withUploadedFiles([]),
            $request->withParsedBody(['p' => 1]),
        ];

        foreach ($withers as $result) {
            $this->assertInstanceOf(ServerRestRequest::class, $result);
            $this->assertNotSame($request, $result);
        }
    }

    public function testWithBodyReturnsNewWrapperInstance(): void
    {
        $inner = $this->makeRequest();
        $inner->method('withBody')->willReturn($this->makeRequest());

        $request = new ServerRestRequest($inner);
        $body = $this->createMock(StreamInterface::class);
        $result = $request->withBody($body);

        $this->assertInstanceOf(ServerRestRequest::class, $result);
        $this->assertNotSame($request, $result);
    }

    public function testWithUriReturnsNewWrapperInstance(): void
    {
        $inner = $this->makeRequest();
        $inner->method('withUri')->willReturn($this->makeRequest());

        $request = new ServerRestRequest($inner);
        $uri = $this->createMock(UriInterface::class);
        $result = $request->withUri($uri);

        $this->assertInstanceOf(ServerRestRequest::class, $result);
        $this->assertNotSame($request, $result);
    }

    // withRequestTarget() is intentionally not tested: it delegates to a method that is
    // not declared on HttpRestRequest, so it cannot be stubbed on the mock. The other 10
    // withers above cover the immutable-wrapper pattern.
}
