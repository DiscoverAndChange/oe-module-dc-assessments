v0.11.1 PHPStan level-10 fixes (bugs + non-ignorable) and module-local baseline

  Fix the genuine bugs and all non-ignorable errors PHPStan surfaced once the
  module could be analyzed in isolation on 8.4.1; baseline the remaining
  pre-existing style debt so CI can run green and the debt is burned down later.

  Runtime (8.4.x) fixes found during GUI testing:
  - API route dispatch: OpenEMR >= 8.2 appends the OEGlobalsBag after the
    HttpRestRequest when invoking a route callback, so APISetupController's closure
    (which assumed the request was the last arg via array_pop) passed the globals
    bag / URL id into controller methods — e.g. SystemUserRestController::one()
    received the :id string where a ServerRestRequest was expected (TypeError).
    Rewrite the closure to locate the HttpRestRequest among the args regardless of
    position, drop other appended objects, and keep the scalar route params in
    order.
  - Session access: replace every direct $_SESSION read with the OpenEMR session
    wrapper (SessionWrapperFactory::getInstance()->getActiveSession()->get()), since
    8.4 stores session data in a Symfony session bag that the $_SESSION superglobal
    no longer reflects. Also correct three reads that used the non-existent key
    'authUserId' to the canonical 'authUserID' (they had been returning null).
  - Entry-point bootstrap: 8.4 removed the root _rest_config.php and moved
    RestConfig into a namespace, so moduleConfig.php and the public/backend
    entry scripts (index-backend.php, questionnaire-audit.php) fatally failed on
    `require_once .../_rest_config.php` ("Failed opening required ... _rest_config.php")
    and the global `RestConfig::emitResponse()`. Drop the dead require and emit the
    PSR-7 response via the module's own RestUtils::emitResponse(); also move the
    backend scripts' $_SESSION['authUser'] read onto the session wrapper.
  - API response gzip vs api_log: RestUtils::returnSingleObjectResponse() wrapped
    the body in a GzipEncodeStream with Content-Encoding: gzip. OpenEMR 8.4's
    ApiResponseLoggerListener logs the response content into the utf8mb4 api_log
    table (both request_body and response columns), so the binary gzip bytes threw
    SQLSTATE[22007] 1366 "Incorrect string value" on every logged API call. Return
    plain JSON instead and let the web server negotiate transport compression.
  - Every module POST/create/update fataled with "Call to a member function
    getContents() on resource": ServerRestRequest::getBodyAsJson() delegated to
    core HttpRestRequest::getRequestBodyJSON(), which on 8.x does
    $this->getContent(true)->getContents() — but HttpRestRequest extends Symfony's
    Request without overriding getContent(), so getContent(true) returns a PHP
    resource (no getContents()). Read the body directly instead: Symfony
    getContent() as a string, gzip-decoded when Content-Encoding: gzip, then
    json_decode. Fixes all 12 getBodyAsJson() call sites (assignment create,
    group/report create+update, client assignment/message, etc.).
  - FHIR Task search returned {"headers":...} instead of a bundle: TaskRest
    Controller::getAll() routed the bundle through RestControllerHelper::
    responseHandler(), which on OpenEMR 8.x returns a Symfony Response — and
    returnSingleObjectResponse() then json_encode()'d that object, emitting only
    its public $headers property. Return the bundle directly (like the sibling
    Questionnaire controllers). Additionally, FHIRBundle omits the `entry` key
    entirely when there are no results, but the SPA expects an array, so the empty
    case is normalized to entry: [] on the Task, Questionnaire, and Questionnaire
    Response list endpoints.
  - AssessmentAppointmentController::deleteDigitalDocumentsSection(): the
    appointment-delete cleanup used array_map() with its arguments swapped
    (array, callback), which fatals; rewrite as a foreach side-effect loop so
    removing an appointment actually clears its assignments.

  Latent bugs:
  - AssessmentRepository::getAssessmentForAssignmentItem(): the query selected
    `ab1.status`, but this query's FROM aliases the tables item/assessment/
    assignment — there is no `ab1` alias here (that alias belongs to the other
    methods). So the query raised "Unknown column 'ab1.status'" (MySQL 1054)
    whenever an assessment was fetched for an assignment item. The status column
    is on the assessment-blob table, aliased `assessment` here → use
    assessment.status.
  - Client::sortAssignmentsByDateAssigned() / fromJSON: `new DateTime()`,
    `new InvalidArgumentException`, and SystemUser::fromJSON `new Exception`
    resolved into the module's Models namespace (non-existent classes) — qualify
    them as \DateTime / \InvalidArgumentException / \Exception.
  - RestUtils: add getAccessDeniedResponse() and getServerErrorResponse(), which
    AssessmentGroup/AssessmentReport/AssessmentResult controllers call on their
    access-denied / error paths but which did not exist (latent fatals).
  - AssessmentGroupRestController: declare the $logger property (was an undeclared
    dynamic property, deprecated on PHP 8.5) typed as SystemLogger.
  - APISetupController: fix the RestApiScopeEvent closure param casing.
  - SystemError: its constructor was misspelled `__constructor` (so `new
    SystemError($code, $message)` silently dropped its arguments and left the
    typed `$_code`/`$_subErrors` properties uninitialized) — rename to
    `__construct`, assign both properties, default `$subErrors` to null, and
    correct `code()` to return int. This also unbreaks the live
    `throw new SystemError(...)` in ClientSearchRepository::getClientList(), which
    additionally lacked a `use` import (it resolved to a non-existent class).
  - HTTPResponseUtils::jsonErrorResponseHandler(): add the missing SystemLogger /
    SystemError / ErrorCodeStatus imports and \Throwable qualifier, drop the
    getName() call (RuntimeException has none), look the HTTP status up from the
    numeric code (not the translated name), replace the non-existent
    ErrorCode::name() with getErrorStringForErrorCode(), and wrap the JSON body in
    a stream for withBody(). ClientRestController now passes $this->logger to it.

  Non-ignorable errors (cannot be baselined):
  - Add `: array` return type to jsonSerialize() in 10 Models (Assignment and its
    subclasses, AssessmentSnippet, AssessmentSummary, Client, SystemUser) to match
    JsonSerializable's tentative return type.
  - ServerRestRequest: mark final and declare PSR-7 native return types on all
    ServerRequestInterface methods (the immutable withers return `: static`), so
    they are covariant with the interface.
  - Fill empty `: ResponseInterface` stubs that returned nothing (return.missing):
    EmptyRestController (one/create/update), AssessmentGroupRestController
    (one/update), QuestionnaireRestController (create/update),
    QuestionnaireResponseRestController (update).

  Tooling:
  - Add module-local phpstan-baseline.neon (included from phpstan.neon.dist) to
    record the remaining pre-existing style findings. It is scoped to this
    module's src/ only — it never references OpenEMR core or other modules.
    Regenerate it with `composer phpstan -- --generate-baseline <path>` in a dev
    checkout (see the file header).
  - Baseline burn-down (round 1): zero-ripple fixes — method/parameter name
    casing (Client::getId/setId, Request::getRequestUri, FhirServiceBase::
    insertOpenEmrRecord overrides) and broken PHPDoc (@param/@return prose, a
    wrong @param name, a @return mixed/FHIRBundle mismatch, and dead @var/@global
    tags in Bootstrap). Also type the five untyped properties (GlobalConfig
    $globalsArray, Role $validRoles, ClientSearchRepository $logger,
    QuestionnaireResponseFHIRResourceService $dispatcher) and drop the dead
    ClientSearchRepository $_repo. Regenerate the baseline to drop the now-fixed
    entries.

v0.11.0 OpenEMR 8.4.1 (PHP 8.5) compatibility

  Port the module to OpenEMR 8.4.1 / PHP 8.5. Verified in a live 8.4.1 container:
  the module registers and bootstraps, the FHIR capability endpoint serves with
  the module's Questionnaire/QuestionnaireResponse resources, and the patient
  frontend and backend config pages load — with no fatals or module deprecations.

  Core API alignment:
  - LoggerAwareTrait: type the $logger property as ?Psr\Log\LoggerInterface and
    align setLogger() to PSR's setLogger(LoggerInterface): void, so it no longer
    clashes with the PSR LoggerAwareTrait that 8.4.1's FhirServiceBase composes.
  - Bootstrap: alias Psr\Log\LoggerInterface to the synthetic SystemLogger
    service so the #[Required] setLogger() setter autowires.
  - FHIR services: match 8.4.1's typed FhirServiceBase::createOpenEMRSearchParameters
    signature (array $fhirSearchParameters, ?string $puuidBind = null): array.
  - Replace SystemLogger::errorLogCaller() (removed in 8.4.1) with PSR-3
    LoggerInterface::error() across the module (~70 call sites).
  - Validators: add ': void' return type to configureValidator() in all 6
    validators to match 8.4.1's BaseValidator::configureValidator(): void.
  - Bootstrap: CsrfUtils::collectCsrfToken() now requires a SessionInterface —
    pass SessionWrapperFactory::getInstance()->getActiveSession() (core's idiom).
  - Cap psr/http-message to ^1.1 (core's version) so the module's PSR-7
    ServerRestRequest stays compatible — psr/http-message 2.0 added return types
    to the interfaces that the 1.x implementation does not declare.

  Dependency modernization (fixes the in-process class collisions that broke
  every REST/FHIR request):
  - Pin bundled Symfony to the same major core ships (^7.4) and cap the
    transitive packages (http-foundation, string, console, type-info,
    var-exporter) so they cannot drift onto Symfony 8.
  - symfony/serializer: upgrade to ^7.4; update FhirObjectDenormalizer to the
    Symfony 7 DenormalizerInterface (typed signatures + getSupportedTypes()).
  - Drop Doctrine entirely (ORM/DBAL) — the module uses OpenEMR QueryUtils / raw
    SQL, not Doctrine; removed the unused OpenEMRDatabaseConnectionWrapper and the
    dead Doctrine\ORM\Query imports.

  NOTE: targets OpenEMR >= 8.4 and is NOT backward-compatible with 7.0.2 (8.4.1
  tightened FhirServiceBase with typed signatures that 7.0.x's untyped base
  rejects). Authenticated FHIR read/write, the full provider UI, and the patient
  portal assignment flow still need end-to-end QA on a populated 8.4.1 instance.
  See PORTING-8.4.1.md.

v0.10.0 Add developer tooling, CLAUDE.md, and project conventions

  Add composer scripts for phpstan, rector, and phpunit that run from the OpenEMR root.
  Add CLAUDE.md with architecture docs, commands, and coding conventions.
  Establish versioning, branching, and PR conventions for AI-assisted development.

v0.9.0 Initial module release

  Release of working assessment module.
