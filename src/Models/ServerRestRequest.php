<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

use Http\Message\Encoding\GzipDecodeStream;
use OpenEMR\Common\Acl\AclMain;
use Psr\Http\Message\RequestInterface;
use OpenEMR\Common\Http\HttpRestRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRestRequest implements ServerRequestInterface
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

    public function getProtocolVersion()
    {
        return $this->httpRestRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        return new ServerRestRequest($this->httpRestRequest->withProtocolVersion($version));
    }

    public function getHeaders()
    {
        return $this->httpRestRequest->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->httpRestRequest->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->httpRestRequest->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->httpRestRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        return new ServerRestRequest($this->httpRestRequest->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return new ServerRestRequest($this->httpRestRequest->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return new ServerRestRequest($this->httpRestRequest->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->httpRestRequest->getBody();
    }

    public function getBodyAsJson()
    {
        return $this->httpRestRequest->getRequestBodyJSON();
    }

    public function withBody(StreamInterface $body)
    {
        return new ServerRestRequest($this->httpRestRequest->withBody($body));
    }

    public function getRequestTarget()
    {
        return $this->httpRestRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        return new ServerRestRequest($this->httpRestRequest->withRequestTarget($requestTarget));
    }

    public function getMethod()
    {
        return $this->httpRestRequest->getMethod();
    }

    public function withMethod($method)
    {
        return new ServerRestRequest($this->httpRestRequest->withMethod($method));
    }

    public function getUri()
    {
        return $this->httpRestRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
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

    public function getServerParams()
    {
        // TODO: Implement getServerParams() method.
        return $this->httpRestRequest->getServerParams();
    }

    public function getCookieParams()
    {
        // TODO: Implement getCookieParams() method.
        return $this->httpRestRequest->getCookieParams();
    }

    public function withCookieParams(array $cookies)
    {
        // TODO: Implement withCookieParams() method.
        return new ServerRestRequest($this->httpRestRequest->withCookieParams($cookies));
    }

    public function getQueryParams()
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

    public function withQueryParams(array $query)
    {
        return new ServerRestRequest($this->httpRestRequest->withQueryParams($query));
    }

    public function getUploadedFiles()
    {
        return $this->httpRestRequest->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        return new ServerRestRequest($this->httpRestRequest->withUploadedFiles($uploadedFiles));
    }

    public function getParsedBody()
    {
        return $this->httpRestRequest->getParsedBody();
    }

    public function withParsedBody($data)
    {
        return new ServerRestRequest($this->httpRestRequest->withParsedBody($data));
    }

    public function getAttributes()
    {
        return $this->httpRestRequest->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->httpRestRequest->getAttribute($name, $default);
    }

    public function withAttribute($name, $value)
    {
        return new ServerRestRequest($this->httpRestRequest->withAttribute($name, $value));
    }

    public function withoutAttribute($name)
    {
        return new ServerRestRequest($this->httpRestRequest->withoutAttribute($name));
    }

    public function getCompanyId()
    {
        // TODO: @adunsulag need to handle the company id here better
        return 1;
    }
}
