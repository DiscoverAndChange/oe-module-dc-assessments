<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Events\RestApiExtend\RestApiResourceServiceEvent;
use OpenEMR\Events\RestApiExtend\RestApiSecurityCheckEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\IStaticEventSubscriber;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class APISetupController implements IStaticEventSubscriber
{
    // need to hook into the OpenEMR FHIR event hook to add to the api
    // need to add the task endpoint to OpenEMR FHIR

    public function __construct()
    {
    }



    /**
     * @return void
     */
    public function addApi(Container $container, RestApiCreateEvent $event)
    {
        foreach (APIProxyController::API_MAPPINGS as $clazz => $mapping) {
            $methods = $mapping['method'] ?? ['GET'];
            foreach ($methods as $method) {
                $contexts = $mapping['context'] ?? ['user'];
                foreach ($contexts as $context) {
                    $function = function (...$args) use ($mapping, $container) {
                        // TODO: @adunsulag check ACL permission checks here for user context
                        // OpenEMR dispatches the route callback with the captured URL
                        // parameters followed by the HttpRestRequest. Since 8.2 it ALSO
                        // appends the OEGlobalsBag after the request, so we can no longer
                        // assume the request is the last argument. Pull the HttpRestRequest
                        // out wherever it is, drop any other appended object (the globals
                        // bag), keep the scalar route params in order, and put a
                        // ServerRestRequest first as our controllers expect.
                        $request = null;
                        $routeParams = [];
                        foreach ($args as $arg) {
                            if ($arg instanceof HttpRestRequest) {
                                $request = $arg;
                            } elseif (is_object($arg)) {
                                // e.g. OEGlobalsBag appended by OpenEMR >= 8.2; not a route param
                                continue;
                            } else {
                                $routeParams[] = $arg;
                            }
                        }
                        // now put the request at the beginning for our routes as that's how our APIs function
                        if ($request instanceof HttpRestRequest) {
                            array_unshift($routeParams, new ServerRestRequest($request));
                        }
                        if (method_exists($mapping['controller'], $mapping['action'])) {
                            $controller = $container->get($mapping['controller']);
                            return call_user_func([$controller, $mapping['action']], ...$routeParams);
                        }
                    };
                    if (isset($mapping['isFhir']) && $mapping['isFhir'] === true) {
                        $route = $method . ' /fhir' . $mapping['path'];
                        $event->addToFHIRRouteMap($route, $function);
                    }
                    if ($context == 'user') {
                        $route = $method . ' /api' . $mapping['path'];
                        $event->addToRouteMap($route, $function);
                    } else if ($context == 'patient') {
                        $route = $method . ' /portal' . $mapping['path'];
                        $event->addToPortalRouteMap($route, $function);
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function addScopes(RestApiScopeEvent $event)
    {
        foreach (APIProxyController::API_MAPPINGS as $clazz => $mapping) {
            $contexts = $mapping['context'] ?? ['user'];
            foreach ($contexts as $context) {
                $resourceScope = explode('.', $mapping['scope']);
                $resource = $resourceScope[0];
                $permission = $resourceScope[1] ?? '';
                if ($context == 'user') {
                    $event->addScope($context, $resource, $permission);
                } else if ($context == 'patient') {
                    $event->addScope($context, $resource, $permission);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function addMetadata(RestApiResourceServiceEvent $event)
    {
//        $event->setServiceClass(TaskFHIRResourceService::class);
//        $event->setServiceClass(QuestionnaireFHIRResourceService::class);
    }

    /**
     * @return void
     */
    public static function subscribeToEvents(Container $container, EventDispatcherInterface $eventDispatcher)
    {

        $eventDispatcher->addListener(RestApiCreateEvent::EVENT_HANDLE, function (RestApiCreateEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->addApi($container, $event);
            }
        });
        $eventDispatcher->addListener(RestApiScopeEvent::EVENT_TYPE_GET_SUPPORTED_SCOPES, function (RestApiScopeEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->addScopes($event);
            }
        });
        $eventDispatcher->addListener(RestApiResourceServiceEvent::EVENT_HANDLE, function (RestApiResourceServiceEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                $service->addMetadata($event);
            }
        });
        $eventDispatcher->addListener(RestApiSecurityCheckEvent::EVENT_HANDLE, function (RestApiSecurityCheckEvent $event) use ($container) {
            $service = $container->get(self::class);
            if ($service instanceof self) {
                // already have a failed response so we aren't going to do anything
                if ($event->hasSecurityCheckFailedResponse()) {
                    return $event;
                }
                if ($service->shouldSkipSecurityForResource($event)) {
                    $event->skipSecurityCheck(true);
                }
            }
        });
    }

    /**
     * @return bool
     */
    private function shouldSkipSecurityForResource(RestApiSecurityCheckEvent $event)
    {
        // we only want to check at the patient level
        if ($event->getScopeType() != 'patient') {
            return false;
        }
        $resource = $event->getResource();
        if (
            $event->getRestRequest()->isPatientWriteRequest()
            && in_array($resource, ['Task', 'QuestionnaireResponse'])
        ) {
            return true; // we want to allow QuestionnaireResponse to be writeable by patient api as we explore this in the module.
        } else {
            return false;
        }
    }
}
