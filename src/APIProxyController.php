<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Nyholm\Psr7\Request;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Http\HttpRestRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AnnouncementRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentGroupRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentReportRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\AssessmentResultRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\ClientRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\EmptyRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\LibraryAssetRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\LibraryAssetResultRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\MessageTemplateRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireResponseRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\SystemUserRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TagRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TaskRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TokenRestController;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Environment;

class APIProxyController
{
    private string $baseUri;

    /**
     * @var array
     */
    private $controllers;

    /**
     * @var RouteCollection
     */
    private $routes;

    /**
     * @var array
     */
    const API_MAPPINGS = [
        'questionnaire.list' => [
            'path' => '/Questionnaire',
            'controller' => QuestionnaireRestController::class,
            'action' => 'list',
            'context' => ['user','patient','system'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'Questionnaire.read',
            'isFhir' => true
        ],
        'questionnaire.one' => [
            'path' => '/Questionnaire/:id',
            'controller' => QuestionnaireRestController::class,
            'action' => 'one',
            'context' => ['user','patient','system'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'Questionnaire.read',
            'isFhir' => true
        ],
        'questionnaire-response.list' => [
            'path' => '/QuestionnaireResponse',
            'controller' => QuestionnaireResponseRestController::class,
            'action' => 'list',
            'method' => ['GET'],
            'context' => ['user','patient','system'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'QuestionnaireResponse.read',
            'isFhir' => true
        ],
        'questionnaire-response.create' => [
            'path' => '/QuestionnaireResponse',
            'controller' => QuestionnaireResponseRestController::class,
            'action' => 'create',
            'method' => ['POST'],
            'context' => ['user','patient','system'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'QuestionnaireResponse.write',
            'isFhir' => true
        ],
        'questionnaire-response.one' => [
            'path' => '/QuestionnaireResponse/:id',
            'controller' => QuestionnaireResponseRestController::class,
            'action' => 'one',
            'context' => ['user','patient','system'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'QuestionnaireResponse.read',
            'isFhir' => true
        ],
        'task.list' => [
            'path' => '/Task',
            'controller' => TaskRestController::class,
            'action' => 'list',
            'context' => ['patient', 'user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'Task.read',
            'isFhir' => true
        ],
        'task.one' => [
            'path' => '/Task/:id',
            'controller' => TaskRestController::class,
            'action' => 'one',
            'context' => ['patient', 'user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'Task.read',
            'isFhir' => true
        ],
        'task.update' => [
            'path' => '/Task/:id',
            'controller' => TaskRestController::class,
            'action' => 'update',
            'context' => ['patient', 'user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'method' => ['PUT'],
            'scope' => 'Task.write',
            'isFhir' => true
        ],
        'clients.list' => [
            'path' => '/reports/clients',
            'controller' => ClientRestController::class,
            'action' => 'list',
            'context' => ['user'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'reports.read'
        ],
        'clients.one' => [
            'path' => '/clients/:id',
            'controller' => ClientRestController::class,
            'action' => 'one',
            'context' => ['patient', 'user'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'clients.read'
        ],
        'clients.one.assignment-groups' => [
            'path' => '/clients/:id/assignment-groups',
            'controller' => ClientRestController::class,
            'action' => 'addAssignmentGroupToClient',
            'context' => ['user'],
            'method' => ['POST'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'assignment-groups.write'
        ],
        'clients.one.assignment.delete' => [
            'path' => '/clients/:id/assignments/:assignmentId',
            'controller' => ClientRestController::class,
            'action' => 'removeAssignmentFromClient',
            'context' => ['user'],
            'method' => ['DELETE'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'assignments.write'
        ],
        'clients.one.assignment.create' => [
            'path' => '/clients/:id/assignments',
            'controller' => ClientRestController::class,
            'action' => 'addAssignmentToClient',
            'context' => ['user'],
            'method' => ['POST'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'assignments.write'
        ],
        'clients.one.messages.create' => [
            'path' => '/clients/:id/messages',
            'controller' => ClientRestController::class,
            'action' => 'sendMessageToClient',
            'context' => ['user'],
            'method' => ['POST'],
            'acl' => [
                ['patients', 'demo']
            ],
            'scope' => 'messages.write'
        ],
        'assessment-groups.list' => [
            'path' => '/assessment-groups',
            'controller' => AssessmentGroupRestController::class,
            'action' => 'list',
            'method' => ['GET'],
            'context' => ['user', 'patient'],
            'acl' => [],
            'scope' => 'assessment-groups.read'
        ],
        'assessment-groups.create' => [
            'path' => '/assessment-groups',
            'controller' => AssessmentGroupRestController::class,
            'action' => 'create',
            'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-groups.write'
        ],
        'assessment-groups.one.assessment.create' => [
            'path' => '/assessment-groups/:groupId/assessments',
            'controller' => AssessmentGroupRestController::class,
            'action' => 'addAssessmentToGroup',
            'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessments.write'
        ],
        'assessment-groups.one.assessment.update' => [
            'path' => '/assessment-groups/:groupId/assessments/$update',
            'controller' => AssessmentGroupRestController::class,
            'action' => 'updateAssessmentVersionForGroup',
            'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-groups.write'
        ],
        'assessment-reports.list' => [
            'path' => '/assessment-reports'
            ,'controller' => AssessmentReportRestController::class
            ,'action' => 'list'
            ,'method' => ['GET'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-reports.read'
        ],
        'assessment-reports.create' => [
            'path' => '/assessment-reports'
            ,'controller' => AssessmentReportRestController::class
            ,'action' => 'create'
            ,'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-reports.write'
        ],
        'assessment-reports.one' => [
            'path' => '/assessment-reports/:id'
            ,'controller' => AssessmentReportRestController::class
            ,'action' => 'one'
            ,'method' => ['GET'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-reports.read'
        ],
        'assessment-reports.one.update' => [
            'path' => '/assessment-reports/:id'
            ,'controller' => AssessmentReportRestController::class
            ,'action' => 'update'
            ,'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-reports.write'
        ],
        'assessment-results.list' => [
            'path' => '/assessment-results'
            ,'controller' => AssessmentResultRestController::class
            ,'action' => 'list'
            ,'method' => ['GET'],
            'context' => ['user'],
            'acl' => [
                ['patient', 'demo']
            ],
            'scope' => 'assessment-results.read'
        ],
        'assessment-results.create' => [
            'path' => '/assessment-results'
            ,'controller' => AssessmentResultRestController::class,
            'action' => 'create'
            ,'method' => ['POST'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessment-results.write'
        ],
        'tags.list' => [
            'path' => '/tags'
            ,'controller' => TagRestController::class,
            'action' => 'list',
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'tags.read'
        ],
        'message-templates.list' => [
            'path' => '/message-templates'
            ,'controller' => MessageTemplateRestController::class,
            'action' => 'list',
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'message-templates.read'
        ],
        'users.list' => [
            'path' => '/assessment-users'
            ,'controller' => SystemUserRestController::class,
            'action' => 'list',
            'context' => ['user'],
            'acl' => [
                ['admin', 'users']
            ],
            'scope' => 'assessment-users.read'
        ],
        'users.one' => [
            'path' => '/assessment-users/:id'
            ,'controller' => SystemUserRestController::class,
            'action' => 'one',
            'context' => ['user'],
            'acl' => [
                ['admin', 'users']
            ],
            'scope' => 'assessment-users.read'
        ],
        'library-assets.list' => [
            'path' => '/library-assets'
            ,'controller' => LibraryAssetRestController::class,
            'action' => 'list'
            ,'method' => ['GET'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'library-assets.read'
        ],
        'library-assets.one' => [
            'path' => '/library-assets/:id'
            ,'controller' => LibraryAssetRestController::class,
            'action' => 'one'
            ,'method' => ['GET'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'library-assets.read'
        ],
        'library-assets.create' => [
            'path' => '/library-assets'
            ,'controller' => LibraryAssetRestController::class,
            'action' => 'create'
            ,'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'library-assets.write'
        ],
        'library-asset-results.create' => [
            'path' => '/library-asset-results'
            ,'controller' => LibraryAssetResultRestController::class,
            'action' => 'create'
            ,'method' => ['POST'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'library-asset-results.write'
        ],
        'library-asset-results.one' => [
            'path' => '/library-asset-results/:id'
            ,'controller' => LibraryAssetResultRestController::class,
            'action' => 'one'
            ,'method' => ['GET'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'library-asset-results.read'
        ],
        'assessments.list' => [
            'path' => '/assessments'
            ,'controller' => AssessmentRestController::class
            ,'action' => 'list'
            ,'method' => ['GET'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessments.read'
        ],
        'assessments.create' => [
            'path' => '/assessments'
            ,'controller' => AssessmentRestController::class
            ,'action' => 'create'
            ,'method' => ['POST'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessments.write'
        ],
        'assessments.one' => [
            'path' => '/assessments/:id'
            ,'controller' => AssessmentRestController::class
            ,'action' => 'one'
            ,'method' => ['GET'],
            'context' => ['user', 'patient'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessments.read'
        ],
        'assessments.update' => [
            'path' => '/assessments/:id'
            ,'controller' => AssessmentRestController::class
            ,'action' => 'update'
            ,'method' => ['POST'],
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'assessments.write'
        ],
        'tokens.list' => [
            'path' => '/tokens'
            ,'controller' => TokenRestController::class,
            'action' => 'list',
            'context' => ['user'],
            'acl' => [
                ['encounters', 'forms']
            ],
            'scope' => 'tokens.read'
        ],
        'announcements.list' => [
            'path' => '/announcements'
            ,'controller' => EmptyRestController::class,
            'action' => 'list',
            'context' => ['user'],
            'acl' => [
            ],
            'scope' => 'announcements.read'
        ]
    ];

    public function __construct(private SystemLogger $logger, private Environment $twig, private GlobalConfig $config, private CryptoGen $cryptoGen)
    {
        // TODO: @adunsulag need to lazy load all this stuff.
        // TODO: @adunsulag need to have this domain URL be configurable
        $this->baseUri = "http://localhost:8000/api/v1";
        $this->routes = new RouteCollection();

        // TODO: @adunsulag look at moving this into a DI container
        $this->controllers = [
            ClientRestController::class => new ClientRestController()
            ,AnnouncementRestController::class => new AnnouncementRestController()
            ,AssessmentGroupRestController::class => new AssessmentGroupRestController()
            ,AssessmentRestController::class => new AssessmentRestController()
            ,SystemUserRestController::class => new SystemUserRestController()
            ,TagRestController::class => new TagRestController()
            ,TokenRestController::class => new TokenRestController()
            ,AssessmentResultRestController::class => new AssessmentResultRestController($this->logger)
            ,AssessmentReportRestController::class => new AssessmentReportRestController($this->logger)
            ,LibraryAssetRestController::class => new LibraryAssetRestController($this->logger)
            ,LibraryAssetResultRestController::class => new LibraryAssetResultRestController($this->logger, $this->cryptoGen)
            ,MessageTemplateRestController::class => new MessageTemplateRestController($this->logger, $this->twig, $this->config)
            ,EmptyRestController::class => new EmptyRestController()
        ];

        $apiPrefix = '/api/v1';
        foreach (self::API_MAPPINGS as $routeName => $route) {
            $this->routes->add($routeName, new Route($apiPrefix . $route['path'], [], [], [], '', [], $route['method'] ?? []));
        }
    }

    public function proxyGet(HttpRestRequest $httpRestRequest)
    {
        $request = $this->createRequestFromHttpRestRequest($httpRestRequest);
        $callable = $this->getCallableForApiRequest($request);
        if (!empty($callable)) {
            return $callable(); // params are embedded in the request object
        } else {
            return $this->sendRequestAndReturnResponse($request);
        }
    }

    public function proxyDelete(HttpRestRequest $httpRestRequest)
    {
        return $this->proxyGet($httpRestRequest);  // gonna be pretty identical here.
    }

    public function proxyPost(HttpRestRequest $httpRestRequest)
    {
        $param = $httpRestRequest->getQueryParam('API_REQUEST');
        $request = $this->createRequestFromHttpRestRequest($httpRestRequest);

        // handle our gzip and application/json encoding parameters.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_CONTENT_ENCODING'] === 'gzip' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $request = $request->withAddedHeader('Content-Encoding', 'gzip')
                ->withAddedHeader('Accept-Encoding', 'gzip');
        }
        $callable = $this->getCallableForApiRequest($request);

        if (!empty($callable)) {
            return $callable(); // params are embedded in the request object
        } else {
            return $this->sendRequestAndReturnResponse($request);
        }
    }

    private function getCallableForApiRequest(ServerRestRequest $request): callable|null
    {
        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($request);
        $context = new RequestContext();
        $context->fromRequest($symfonyRequest);
        $matcher = new UrlMatcher($this->routes, $context);
        try {
            $attributes = $matcher->match($symfonyRequest->getPathInfo());
            $route = $this->routeMappings[$attributes['_route']];
            $controller = $this->controllers[$route['controller']];
            $this->logger->debug(self::class . "->getCallableForApiRequest() called", ['route' => $route, 'attributes' => $attributes]);
            unset($attributes['_route']);
            $attributes['request'] = $request;
            return function () use ($controller, $route, $attributes) {
                return call_user_func([$controller, $route['action']], ...$attributes);
            };
        } catch (ResourceNotFoundException $e) {
            // not really anything to do since we are proxying the requests so we return null here;
            (new SystemLogger())->debug("APIProxyController() " . $e->getMessage());
            return null;
        }
    }
// /api/v1/admin/assessment-reports/
// /api/v1/library-assets/
// /api/v1/message-templates/
// /api/v1/assessment-groups.json
// /api/v1/tags/
// /api/v1assessments
// /api/v1users/


    public function createRequestFromHttpRestRequest(HttpRestRequest $httpRestRequest)
    {
        $queryVars = $httpRestRequest->getQueryParams();
        $psr17Factory = new Psr17Factory();
//        $uri = $this->getUriForApiRequest($queryVars['API_REQUEST']);
//        unset($queryVars['API_REQUEST']);
//        if (!empty($queryVars)) {
//            $uri .= "?" . http_build_query($queryVars);
//        }
        $request = $psr17Factory->createServerRequest($_SERVER['REQUEST_METHOD'], $httpRestRequest->getRequestURI());
        // oddly Psr17Factory does not set the query params array.
        $request = $request->withQueryParams($queryVars);
//        $request = $this->addAuthorizationToRequest($httpRestRequest, $request);


        $request = $request->withAddedHeader('Content-Type', 'application/json')
            ->withAddedHeader('Accept', 'application/json');

//        $request = $this->addAuthorizationToRequest($request);

        $body = file_get_contents('php://input');
        if (!empty($body)) {
            // TODO: @adunsulag Nyholm PSR points the stream at the end of the contents... really odd
            // so we have to rewind the stream when creating the body.
            $stream = $psr17Factory->createStream($body);
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        return new ServerRestRequest($httpRestRequest, $request);
    }
    private function getUriForApiRequest($apiRequest)
    {
        $uri = $this->baseUri . $apiRequest;
        return $uri;
    }

    private function addAuthorizationToRequest(HttpRestRequest $request, RequestInterface $proxyRequest)
    {
        $authorization = $request->getHeader("Authorization")[0] ?? '';
        if (!empty($authorization)) {
            // no need to validate the authorization as OpenEMR has already done that by going through the api.
            $proxyRequest = $proxyRequest->withAddedHeader('Authorization', $authorization);
        }
        return $proxyRequest;
    }

    private function sendRequestAndReturnResponse(ServerRestRequest $request)
    {
        try {
            $client = new Client();
            $response = $client->send($request);
        } catch (GuzzleException $e) {
            (new SystemLogger())->errorLogCaller(
                $e->getMessage(),
                ['trace' => $e->getTraceAsString(), 'apiRequest' => $_REQUEST['API_REQUEST']]
            );
            if ($e->getCode() == 401 || $e->getCode() == 404) {
                $psr17Factory = new Psr17Factory();
                $response = $psr17Factory->createResponse($e->getCode());
            } else {
                $psr17Factory = new Psr17Factory();
                // we don't want someone playing around with the API other than the above status codes.
                $response = $psr17Factory->createResponse(500);
            }
        }
        return $response;
    }
}
