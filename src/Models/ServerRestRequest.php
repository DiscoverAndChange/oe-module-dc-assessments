<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

use Http\Message\Encoding\GzipDecodeStream;
use OpenEMR\Common\Acl\AclMain;
use Psr\Http\Message\RequestInterface;
use OpenEMR\Common\Http\HttpRestRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Thin PSR-7 ServerRequestInterface wrapper around OpenEMR's HttpRestRequest.
 *
 * Declared final so the PSR-7 immutable-wither methods can declare a native
 * `: static` return type (which, for a final class, PHPStan resolves to this
 * class) and stay covariant with the interface.
 */
final class ServerRestRequest implements ServerRequestInterface
{
    /**
     * @var HttpRestRequest $httpRestRequest
     */
    private $httpRestRequest;

    public function __construct(HttpRestRequest $httpRestRequest)
    {
        $this->httpRestRequest = $httpRestRequest;
    }

    public function getAuthRole()
    {
        if ($this->httpRestRequest->isPatientRequest()) {
            return Role::Client;
        } else if (AclMain::aclCheckCore("super", "admin")) {
            return Role::SuperUser;
        } else {
            // not sure if we should have this be a registered user or an admin
            return Role::Registered;
        }
    }

    public function isPatientRequest()
    {
        return $this->httpRestRequest->isPatientRequest();
    }

    public function getPatientUUIDString()
    {
        return $this->httpRestRequest->getPatientUUIDString();
    }

    public function getProtocolVersion(): string
    {
        return $this->httpRestRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version): static
    {
        return new ServerRestRequest($this->httpRestRequest->withProtocolVersion($version));
    }

    public function getHeaders(): array
    {
        return $this->httpRestRequest->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->httpRestRequest->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->httpRestRequest->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->httpRestRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value): static
    {
        return new ServerRestRequest($this->httpRestRequest->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value): static
    {
        return new ServerRestRequest($this->httpRestRequest->withAddedHeader($name, $value));
    }

    public function withoutHeader($name): static
    {
        return new ServerRestRequest($this->httpRestRequest->withoutHeader($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->httpRestRequest->getBody();
    }

    public function getBodyAsJson()
    {
        // Read the raw request body as a string and decode it ourselves. We do NOT
        // delegate to HttpRestRequest::getRequestBodyJSON(): on OpenEMR 8.x that
        // calls $this->getContent(true)->getContents(), but HttpRestRequest extends
        // Symfony's Request without overriding getContent(), so getContent(true)
        // returns a PHP resource — which has no getContents() — and every module
        // POST fatals ("Call to a member function getContents() on resource").
        $content = $this->httpRestRequest->getContent();
        if (!is_string($content) || $content === '') {
            return null;
        }
        // Support gzip-encoded request bodies (the SPA may send Content-Encoding: gzip).
        $encoding = $this->httpRestRequest->headers->get('Content-Encoding');
        if ($encoding !== null && stripos($encoding, 'gzip') !== false) {
            $decoded = @gzdecode($content);
            if ($decoded !== false) {
                $content = $decoded;
            }
        }
        return json_decode($content, true);
    }

    public function withBody(StreamInterface $body): static
    {
        return new ServerRestRequest($this->httpRestRequest->withBody($body));
    }

    public function getRequestTarget(): string
    {
        return $this->httpRestRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget): static
    {
        return new ServerRestRequest($this->httpRestRequest->withRequestTarget($requestTarget));
    }

    public function getMethod(): string
    {
        return $this->httpRestRequest->getMethod();
    }

    public function withMethod($method): static
    {
        return new ServerRestRequest($this->httpRestRequest->withMethod($method));
    }

    public function getUri(): UriInterface
    {
        return $this->httpRestRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        return new ServerRestRequest($this->httpRestRequest->withUri($uri, $preserveHost));
    }

    public function getUserId()
    {
        return $this->httpRestRequest->getRequestUserId();
    }

    public function getHttpRestRequest(): HttpRestRequest
    {
        return $this->httpRestRequest;
    }

    /** @return array<mixed> */
    public function getServerParams(): array
    {
        // TODO: Implement getServerParams() method.
        return $this->httpRestRequest->getServerParams();
    }

    /** @return array<mixed> */
    public function getCookieParams(): array
    {
        // TODO: Implement getCookieParams() method.
        return $this->httpRestRequest->getCookieParams();
    }

    /** @param array<mixed> $cookies */
    public function withCookieParams(array $cookies): static
    {
        // TODO: Implement withCookieParams() method.
        return new ServerRestRequest($this->httpRestRequest->withCookieParams($cookies));
    }

    /** @return array<mixed> */
    public function getQueryParams(): array
    {
        $queryParams = $this->httpRestRequest->getQueryParams();
        // we need to handle cross site debugging in our requests which trigger debug sessions
        // but we don't want to mess up the server query params in our rest request so we purge
        // the debug key here
        if (!empty($queryParams['XDEBUG_SESSION'])) {
            unset($queryParams['XDEBUG_SESSION']);
        }
        return $queryParams;
    }

    /** @param array<mixed> $query */
    public function withQueryParams(array $query): static
    {
        return new ServerRestRequest($this->httpRestRequest->withQueryParams($query));
    }

    /** @return array<mixed> */
    public function getUploadedFiles(): array
    {
        return $this->httpRestRequest->getUploadedFiles();
    }

    /** @param array<mixed> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        return new ServerRestRequest($this->httpRestRequest->withUploadedFiles($uploadedFiles));
    }

    /** @return array<mixed>|object|null */
    public function getParsedBody()
    {
        return $this->httpRestRequest->getParsedBody();
    }

    /** @param array<mixed>|object|null $data */
    public function withParsedBody($data): static
    {
        return new ServerRestRequest($this->httpRestRequest->withParsedBody($data));
    }

    /** @return array<mixed> */
    public function getAttributes(): array
    {
        return $this->httpRestRequest->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->httpRestRequest->getAttribute($name, $default);
    }

    public function withAttribute($name, $value): static
    {
        return new ServerRestRequest($this->httpRestRequest->withAttribute($name, $value));
    }

    public function withoutAttribute($name): static
    {
        return new ServerRestRequest($this->httpRestRequest->withoutAttribute($name));
    }

    public function getCompanyId()
    {
        // TODO: @adunsulag need to handle the company id here better
        return 1;
    }
}
