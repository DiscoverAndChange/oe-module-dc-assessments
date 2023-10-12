<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

use http\Env;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ScopeRepository;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Core\ScriptFilterEvent;
use OpenEMR\Events\Core\TemplatePageEvent;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\SMART\SMARTLaunchToken;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\AssessmentAppointmentController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\AssignmentEncounterController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\BackendDispatchController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\ConfigController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers\FrontendDispatchController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Factory\TwigEnvironmentFactory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\QuestionnaireAssignmentListener;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners\QuestionnaireResponseRestListener;
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
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireAuditController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireResponseRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\QuestionnaireRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\SystemUserRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TagRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TaskRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\RestControllers\TokenRestController;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentResultRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentCompleter;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientMessageDispatcher;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentResponseBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\LibraryAssetResultBlobFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\QuestionnaireFormFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\FhirServices\AssessmentFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTMLSanitizer;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SimplifiedOAuthTwigExtension;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\AssignmentTaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\QuestionnairePortalTaskFHIRResourceService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TaskFHIRResourceService;
use OpenEMR\RestControllers\SMART\SMARTAuthorizationController;
use OpenEMR\Services\LogoService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\QuestionnaireResponseService;
use OpenEMR\Services\QuestionnaireService;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    private const CONTAINER_CACHE_FILE = __DIR__ . '/../cache/container.php';

    private const DEBUG = false;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $moduleDirectoryName;

    /**
     * @var SystemLogger
     */
    private $logger;

    /**
     * @var Container
     */
    private $serviceContainer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig
     */
    private $globalsConfig;

    private static ?self $instance = null;

    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";

    private Kernel $kernel;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        if (empty($kernel)) {
            $kernel = new Kernel();
        }
        $this->kernel = $kernel;

        $this->moduleDirectoryName = basename(dirname(__DIR__));
        $this->eventDispatcher = $eventDispatcher;

        // we inject our globals value.
        $this->globalsConfig = new GlobalConfig($GLOBALS);
        $this->logger = new SystemLogger();
        $this->serviceContainer = $this->setupContainer();
    }

    public static function instantiate(EventDispatcher $eventDispatcher, Kernel $kernel): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new Bootstrap($eventDispatcher, $kernel);
            self::$instance->subscribeToEvents();
        }
        return self::$instance;
    }

    public function addGlobalSettings()
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addModuleGlobalSettings']);
    }

    public function addModuleGlobalSettings(GlobalsInitializedEvent $event)
    {
        $service = $event->getGlobalsService();
        $this->globalsConfig->setupConfiguration($service);
    }

    private function setupContainer(): Container
    {
        // the container cache will check the service definitions, detect file changes and update the cache if there are
        // new definitions as needed.
        $containerConfigCache = new ConfigCache(self::CONTAINER_CACHE_FILE, self::DEBUG);
        if (!$containerConfigCache->isFresh()) {
            $container = new ContainerBuilder();
            $this->addSyntheticServicesToContainer($container);
            $this->addServicesToContainer($container);
            $container->compile();
            $dumper = new PhpDumper($container);
            $containerConfigCache->write(
                $dumper->dump(['class' => 'DacAssessmentCachedContainer', 'namespace' => 'OpenEMR\\Modules\\DiscoverAndChange\\Assessments']),
                $container->getResources()
            );
        }
        require_once self::CONTAINER_CACHE_FILE;
        $container = new DacAssessmentCachedContainer();
        // inject our service values that come from outside our module.
        $this->injectSyntheticServicesIntoContainer($container);
         return $container;
    }
    private function addSyntheticServicesToContainer(ContainerBuilder $container)
    {
        // setup our synthetic services.
        $syntheticServices = ['logger', 'config', 'dispatcher', 'kernel'];
        foreach ($syntheticServices as $service) {
            $definition = $container->setDefinition($service, new Definition())->setSynthetic(true);
            if ($service == 'logger') {
                $definition->setAutowired(true);
            }
        }
        $container->setAlias(SystemLogger::class, 'logger');
    }

    private function injectSyntheticServicesIntoContainer(Container $container)
    {
        $container->set('logger', $this->logger);
        $container->set('kernel', $this->kernel);
        $container->set('config', $this->globalsConfig);
        $container->set('dispatcher', $this->eventDispatcher);
    }
    private function addServicesToContainer(ContainerBuilder $container)
    {
        $publicServices = [];
        $publicServices['crypto'] = new Definition(CryptoGen::class, []);
        $publicServices['sanitizer'] = new Definition(HTMLSanitizer::class, []);
        $publicServices[TwigEnvironmentFactory::class] = new Definition(
            TwigEnvironmentFactory::class,
            [new Reference(SimplifiedOAuthTwigExtension::class), new Reference('kernel'), $this->getTemplatePath()]
        );

        // setup our twig factory
        $publicServices[Environment::class] = new Definition(Environment::class, []);
        $publicServices[Environment::class]->setFactory(new Reference(TwigEnvironmentFactory::class));

        $publicServices[ServerConfig::class] = new Definition(ServerConfig::class, []);
        $publicServices[APISetupController::class] = new Definition(APISetupController::class, []);
        $publicServices[PatientService::class] = new Definition(PatientService::class, []);
        $publicServices[ClientMessageDispatcher::class] = new Definition(
            ClientMessageDispatcher::class,
            [new Reference('logger'), new Reference('dispatcher'), new Reference('sanitizer'), new Reference('config')]
        );
        $publicServices[AssignmentCompleter::class] = new Definition(
            AssignmentCompleter::class,
            [new Reference('logger'), new Reference(ClientMessageDispatcher::class), new Reference('config')]
        );
        $publicServices[AssessmentResultRestController::class] = new Definition(
            AssessmentResultRestController::class,
            [new Reference('logger'), new Reference(AssignmentCompleter::class)]
        );
        $publicServices[AssessmentReportRestController::class] = new Definition(AssessmentReportRestController::class, [new Reference('logger')]);
        $publicServices[LibraryAssetRestController::class] = new Definition(LibraryAssetRestController::class, [new Reference('logger')]);
        $publicServices[LibraryAssetResultRestController::class] = new Definition(
            LibraryAssetResultRestController::class,
            [new Reference('logger')
                , new Reference('crypto')
                ,
                new Reference(AssignmentCompleter::class)]
        );
        $publicServices[AssessmentRepository::class] = new Definition(AssessmentRepository::class, [new Reference('logger')]);
        $publicServices[LibraryAssetBlobRepository::class] = new Definition(LibraryAssetBlobRepository::class, [new Reference('logger')]);
        $publicServices[LibraryAssetResultBlobRepository::class] = new Definition(LibraryAssetResultBlobRepository::class, [new Reference('logger'), new Reference('crypto')]);
        $publicServices[AssessmentFHIRResourceService::class] = new Definition(AssessmentFHIRResourceService::class, [new Reference(AssessmentRepository::class)]);
        $publicServices[LibraryAssetFHIRResourceService::class] = new Definition(LibraryAssetFHIRResourceService::class, [new Reference(LibraryAssetBlobRepository::class)]);
        $publicServices[QuestionnaireFormFHIRResourceService::class] = new Definition(QuestionnaireFormFHIRResourceService::class, []);
        $publicServices[QuestionnaireFHIRResourceService::class] = new Definition(
            QuestionnaireFHIRResourceService::class,
            [
                new Reference(AssessmentFHIRResourceService::class)
                , new Reference(QuestionnaireFormFHIRResourceService::class)
                , new Reference(LibraryAssetFHIRResourceService::class)
            ]
        );
        $publicServices[QuestionnaireRestController::class] = new Definition(
            QuestionnaireRestController::class,
            [new Reference('logger'), new Reference(QuestionnaireFHIRResourceService::class)]
        );
        $publicServices[MessageTemplateRestController::class] = new Definition(MessageTemplateRestController::class, [new Reference('logger')
            , new Reference(Environment::class), new Reference('config')]);
        $publicServices[EmptyRestController::class] = new Definition(EmptyRestController::class, []);
        $publicServices[ClientRestController::class] = new Definition(ClientRestController::class, [new Reference('logger'), new Reference(ClientMessageDispatcher::class)]);
        $publicServices[AnnouncementRestController::class] = new Definition(AnnouncementRestController::class, []);
        $publicServices[AssessmentRestController::class] = new Definition(AssessmentRestController::class, []);
        $publicServices[SystemUserRestController::class] = new Definition(SystemUserRestController::class, []);
        $publicServices[TagRestController::class] = new Definition(TagRestController::class, []);
        $publicServices[TokenRestController::class] = new Definition(TokenRestController::class, []);
        $publicServices[AssessmentGroupRestController::class] = new Definition(AssessmentGroupRestController::class, []);
        $publicServices[QuestionnaireResponseFHIRResourceService::class] = new Definition(QuestionnaireResponseFHIRResourceService::class, [new Reference('dispatcher')]);
        $publicServices[QuestionnaireResponseRestController::class] = new Definition(QuestionnaireResponseRestController::class, [new Reference(QuestionnaireResponseFHIRResourceService::class)]);
        $publicServices[AssignmentRepository::class] = new Definition(AssignmentRepository::class, []);

        $publicServices[QuestionnairePortalTaskFHIRResourceService::class] = new Definition(QuestionnairePortalTaskFHIRResourceService::class, []);
        $publicServices[AssignmentTaskFHIRResourceService::class] = new Definition(AssignmentTaskFHIRResourceService::class, [new Reference(AssignmentRepository::class)]);
        $publicServices[TaskFHIRResourceService::class] = new Definition(
            TaskFHIRResourceService::class,
            [new Reference(QuestionnairePortalTaskFHIRResourceService::class)
                ,
                new Reference(AssignmentTaskFHIRResourceService::class)]
        );
        $publicServices[TaskRestController::class] = new Definition(
            TaskRestController::class,
            [new Reference(TaskFHIRResourceService::class)]
        );
        $publicServices[QuestionnaireResponseService::class] = new Definition(QuestionnaireResponseService::class, []);
        $publicServices[QuestionnaireService::class] = new Definition(QuestionnaireService::class, []);
        $publicServices[QuestionnaireAuditController::class] = new Definition(QuestionnaireAuditController::class, [
            new Reference('logger')
            , new Reference(Environment::class)
            , new Reference(QuestionnaireService::class)
            , new Reference(QuestionnaireResponseService::class)
            , new Reference(AssignmentRepository::class)
            , new Reference("config")
        ]);
        $publicServices[QuestionnaireAuditController::class]->addArgument($this->getURLPath());
        $publicServices[SmartAppClientService::class] = new Definition(SmartAppClientService::class, [new Reference('config')]);
        $publicServices[AssessmentAppointmentController::class] = new Definition(
            AssessmentAppointmentController::class,
            [new Reference(Environment::class), new Reference(AssignmentRepository::class), new Reference('dispatcher'), new Reference('config'), new Reference(SmartAppClientService::class)]
        );
        $publicServices[QuestionnaireResponseOnSiteDocumentService::class] = new Definition(QuestionnaireResponseOnSiteDocumentService::class, [new Reference(QuestionnaireResponseService::class)]);
        $publicServices[QuestionnaireAssignmentListener::class] = new Definition(QuestionnaireAssignmentListener::class, [new Reference(AssignmentRepository::class), new Reference(QuestionnaireResponseOnSiteDocumentService::class)]);

        $publicServices[AssignmentEncounterController::class] = new Definition(AssignmentEncounterController::class, [new Reference(Environment::class)
            , new Reference(AssignmentRepository::class), new Reference('config'), new Reference('logger')]);

        $publicServices[ConfigController::class] = new Definition(ConfigController::class, [new Reference('config'), new Reference(Environment::class)]);
        $publicServices[BackendDispatchController::class] = new Definition(
            BackendDispatchController::class,
            [new Reference(AssessmentAppointmentController::class), new Reference(ConfigController::class)]
        );

        $publicServices[AssessmentResultRepository::class] = new Definition(AssessmentResultRepository::class, []);
        $publicServices[AssessmentResponseBlobFHIRResourceService::class] =
            new Definition(
                AssessmentResponseBlobFHIRResourceService::class,
                [
                    new Reference(AssessmentResultRepository::class)
                    , new Reference(AssignmentRepository::class)
                    , new Reference(AssignmentCompleter::class)
                    , new Reference(PatientService::class)
                ]
            );
        $publicServices[AssessmentResponseBlobFHIRResourceService::class]->setAutowired(true);
        $publicServices[LibraryAssetResultBlobFHIRResourceService::class] = new Definition(
            LibraryAssetResultBlobFHIRResourceService::class,
            [new Reference(LibraryAssetResultBlobRepository::class), new Reference(PatientService::class)
            ,
            new Reference(AssignmentCompleter::class),
            new Reference(AssignmentRepository::class)]
        );
        $publicServices[LibraryAssetResultBlobFHIRResourceService::class]->setAutowired(true);
        $publicServices[QuestionnaireResponseRestListener::class] = new Definition(
            QuestionnaireResponseRestListener::class,
            [new Reference(AssessmentResponseBlobFHIRResourceService::class), new Reference(LibraryAssetResultBlobFHIRResourceService::class)]
        );

        $publicServices[ScopeRepository::class] = new Definition(ScopeRepository::class, []);
        $publicServices[LogoService::class] = new Definition(LogoService::class, []);
        $publicServices[SimplifiedOAuthTwigExtension::class] = new Definition(
            SimplifiedOAuthTwigExtension::class,
            [
                new Reference(ScopeRepository::class)
                , new Reference(LogoService::class)
                , new Reference('config')
            ]
        );

        $publicServices[FrontendDispatchController::class] = new Definition(FrontendDispatchController::class, [
            new Reference('config')
            ,new Reference(SmartAppClientService::class)
            ,new Reference(Environment::class)
            ,new Reference("dispatcher")
        ]);

        $publicServices[APISetupController::class] = new Definition(APISetupController::class, []);

        foreach ($publicServices as $service) {
            $service->setPublic(true);
        }
        $container->addDefinitions($publicServices);
    }

    private function getAssetPath($tier = 'backend')
    {
        return $this->getURLPath() . $tier . '/assets/';
    }

    public function getURLPath()
    {
        return $GLOBALS['webroot'] . self::MODULE_INSTALLATION_PATH . $this->moduleDirectoryName . "/public/";
    }

    public function subscribeToEvents()
    {
        // any events would go here.
        $this->addGlobalSettings();
        $this->registerMenuItems();
        $serviceContainer = $this->getServiceContainer();
        // TODO: @adunsulag lookup syntax for injecting the actual service container into this controller so we can add it to the DI.
        $this->eventDispatcher->addListener(ScriptFilterEvent::EVENT_NAME, [$this, 'addProviderPortalScript']);
        $this->eventDispatcher->addListener(TemplatePageEvent::class, [$this, 'oauth2TemplatePageOverrides']);
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, [$this, 'addTemplateOverrideLoader']);

        // TODO: @adunsulag this has code smell all over it... until we can figure out how to better handle the twig dependency between modules we will go
        // with static instantiation and allow the classes that use listeners to retrieve themselves.
        APISetupController::subscribeToEvents($this->serviceContainer, $this->eventDispatcher);

        AssessmentAppointmentController::subscribeToEvents($this->serviceContainer, $this->eventDispatcher);
//        $serviceContainer->get(AssessmentAppointmentController::class)->subscribeToEvents($this->eventDispatcher);
        AssignmentEncounterController::subscribeToEvents($this->serviceContainer, $this->eventDispatcher);
        QuestionnaireAssignmentListener::subscribeToEvents($this->serviceContainer, $this->eventDispatcher);
        QuestionnaireResponseRestListener::subscribeToEvents($this->serviceContainer, $this->eventDispatcher);
    }

    public function oauth2TemplatePageOverrides(TemplatePageEvent $event)
    {
        $template = $event->getPageName();
        if ($template == 'oauth2/authorize/login') {
            if ($this->globalsConfig->shouldDisplayUpdatedOAuthPages()) {
                $event->setTwigTemplate('discoverandchange/oauth2/oauth2-login.html.twig');
            }
        } else if ($template == 'oauth2/authorize/scopes-authorize') {
            if ($this->globalsConfig->shouldDisplayUpdatedOAuthPages()) {
                if (!empty($_SESSION['pid'])) {
                    $vars = $event->getTwigVariables();
                    if (!empty($vars['scopesByResource']['Questionnaire'])) {
                        $event->setTwigTemplate('discoverandchange/oauth2/scope-authorize.html.twig');
                    }
                }
            }
        } else if ($template == 'oauth2/authorize/patient-select') {
            if ($this->globalsConfig->shouldDisplayUpdatedOAuthPages()) {
                $event->setTwigTemplate('discoverandchange/oauth2/patient-select.html.twig');
            }
        }
        // TODO: need to look at the scope login page to see if we override that as well...
        return $event;
    }

    public function getServiceContainer()
    {
        if (empty($this->serviceContainer)) {
            $this->serviceContainer = $this->setupContainer();
        }
        return $this->serviceContainer;
    }

    public function addTemplateOverrideLoader(TwigEnvironmentEvent $event)
    {
        // TODO: @adunsulag figure out why this is getting fired twice.
        $container = $this->getServiceContainer();
        $twig = $event->getTwigEnvironment();
        // we know if we don't have our twig extension that we haven't executed so we can setup our system this way.
        if (!$twig->hasExtension(SimplifiedOAuthTwigExtension::class)) {
            $twig->addExtension($container->get(SimplifiedOAuthTwigExtension::class));

            // we make sure we can override our file system directory here.
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->getTemplatePath());
            }
        }
    }

    public function addProviderPortalScript(ScriptFilterEvent $event)
    {
        if ($event->getContextArgument(ScriptFilterEvent::CONTEXT_ARGUMENT_SCRIPT_NAME) == '/portal/patient/index.php') {
            $scripts = $event->getScripts();
            $scripts[] = $this->getAssetPath() . '/js/providerPortal.js';
            $event->setScripts($scripts);
        }
    }

    private function getTemplatePath()
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }


    public function registerMenuItems()
    {
//        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_MENU)) {
            /**
             * @var EventDispatcherInterface $eventDispatcher
             * @var array $module
             * @global                       $eventDispatcher @see ModulesApplication::loadCustomModule
             * @global                       $module @see ModulesApplication::loadCustomModule
             */
            $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'addCustomModuleMenuItem']);
//        }
    }

    public function addCustomModuleMenuItem(MenuEvent $event)
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'msc';
        $menuItem->menu_id = 'misimg';
        $menuItem->label = xlt("Patient Portal Assignments");
        $smartAppService = $this->getServiceContainer()->get(SmartAppClientService::class);
        $clientId = $smartAppService->getRegisteredClientId();

        // TODO: pull the install location into a constant into the codebase so if OpenEMR changes this location it
        // doesn't break any modules.
//        $menuItem->url = "/interface/modules/custom_modules/oe-module-dc-assessments/public/frontend/login";
        $menuItem->url = $GLOBALS['webroot'] . '/interface/smart/ehr-launch-client.php?client_id='
            . urlencode($clientId) . '&intent=' . urlencode(SMARTLaunchToken::INTENT_MAIN_TAB)
            . '&csrf_token=' . urlencode(CsrfUtils::collectCsrfToken());
        $menuItem->children = [];

        /**
         * This defines the Access Control List properties that are required to use this module.
         * Several examples are provided
         */
        $menuItem->acl_req = [];

        /**
         * If you would like to restrict this menu to only logged in users who have access to see all user data
         */
        //$menuItem->acl_req = ["admin", "users"];

        /**
         * If you would like to restrict this menu to logged in users who can access patient demographic information
         */
        //$menuItem->acl_req = ["users", "demo"];


        /**
         * This menu flag takes a boolean property defined in the $GLOBALS array that OpenEMR populates.
         * It allows a menu item to display if the property is true, and be hidden if the property is false
         */
        //$menuItem->global_req = ["custom_skeleton_module_enable"];

        /**
         * If you want your menu item to allows be shown then leave this property blank.
         */
        $menuItem->global_req = [];

        foreach ($menu as $item) {
            if ($item->menu_id == 'misimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $event->setMenu($menu);

        return $event;
    }

    public function getQuestionnaireAuditController(): QuestionnaireAuditController
    {
        return $this->getServiceContainer()->get(QuestionnaireAuditController::class);
    }

    public function getBackendDispatchController(): BackendDispatchController
    {
        return $this->getServiceContainer()->get(BackendDispatchController::class);
    }

    public function getClientId()
    {
        $appService = $this->getServiceContainer()->get(SmartAppClientService::class);
        return $appService->getRegisteredClientId();
    }

    public function getFhirUrl()
    {
        $serverConfig = $this->getServiceContainer()->get(ServerConfig::class);
        return $serverConfig->getFhirUrl();
    }

    public function getApiUrl()
    {
        $serverConfig = $this->getServiceContainer()->get(ServerConfig::class);
        return $serverConfig->getStandardApiUrl();
    }

    public function getApiBaseUrl()
    {
        $serverConfig = $this->getServiceContainer()->get(ServerConfig::class);
        return $serverConfig->getBaseApiUrl();
    }

    public function getSmartStyleUrl()
    {
        return $GLOBALS['site_addr_oath'] . $GLOBALS['web_root'] . "/oauth2/" . $_SESSION['site_id'] . "/" . SMARTAuthorizationController::SMART_STYLE_URL;
    }
}
