v0.12.0 PHPStan source-typing pass (mixed-at-boundary cluster)

  Burn down the largest remaining PHPStan class: ~771 errors rooted in `mixed`
  values from DB rows, json_decode, and request payloads flowing into typed slots
  (argument.type, offsetAccess, return.type, binaryOp, method.nonObject,
  foreach.nonIterable, nullCoalesce.*). Fix by typing at the SOURCE — inline
  `/** @var <shape> */` on the DB-row / decoded-json / request-payload / in-code-map
  assignments, with honest shapes (nullable columns -> ?string; ids read via
  string->int casts, never mixed casts). NOT by casting mixed (this build's strict
  rules reject casting mixed, which would only relabel the error as cast.*).

  Per the honesty rule, genuinely-nullable or genuinely-mixed values flowing into
  non-null params were left for the baseline rather than force-typed — those are
  real "handle the null/narrow the value" points, not noise. Baseline: 1418 -> 882
  errors (523 entries); the cluster's container-mixed errors (offsetAccess/foreach/
  invalidOffset) are essentially eliminated (offsetAccess 212->2, foreach 22->0,
  invalidOffset 30->0), with argument.type 353->192 and return.type 49->10. Zero
  cast.int/cast.string introduced; no new non-ignorable errors. Applying the baseline
  reports zero errors and the test suite stays green (176 tests / 589 assertions).

  Real bugs found and fixed along the way (type analysis made them visible):
  - APIProxyController referenced an undefined `$this->routeMappings` property
    (always null -> proxy routing dead); use the real `self::API_MAPPINGS` source.
  - ResourceImporterService referenced an undefined `$report` on its import-error
    logging paths (loop var is `$blob`).
  - AssessmentAppointmentController referenced an undefined `$appt` when building a
    notification payload (should be `$appointment`).
  - AssignedQuestionnaire::fromJSON defaulted a missing questionnaireId to int `0`
    into a string setter (TypeError under strict_types); default to ''.
  - QuestionnaireResponseFHIRResourceService::getAll called setInternalErrors() on a
    non-ProcessingResult event value in one branch; refactored to the real result.

  Known issues recorded in TEST-PLAN.md for follow-up (NOT changed here): a handful
  of residual typing ripples baselined (shapes missing an optional key, a couple of
  over-typed empty() guards) to clean up; and several latent runtime bugs the pass
  surfaced but that need author decisions — AssignmentTaskFHIRResourceService::update()
  calls a non-existent updateAssignmentItem() (runtime fatal on that branch),
  AssessmentReportRepository / TagRestController reference an undefined `$this->logger`,
  ClientRepository::... setAssessmentId() with no null guard, createAssessmentGroups
  FromEntities `return;` that should be `continue`, and Client::fromJSON() dead/broken.

v0.11.2 Test suite expansion + latent-bug fixes surfaced by it

  Begin building out automated test coverage (see TEST-PLAN.md) as a safety net
  before the PHPStan source-typing refactor and for keeping the module in sync
  with OpenEMR core. Added pure-unit characterization tests across the Models,
  the Assigned* subclasses, the DTOs, the error/code maps, and the repository
  hydration methods (private hydrate*FromRecord, exercised via reflection on the
  no-DB path). Coverage ~9% -> ~18%; suite 8 -> 176 tests. Added a coverage-ready
  phpunit.xml (`<source>` = src/, module testsuite) for PCOV reports.

  Fixes for real runtime bugs the characterization tests surfaced:
  - SystemUser::$_companyName had no initializer, so jsonSerialize() on a freshly
    constructed user threw "typed property must not be accessed before
    initialization". Default it to ''.
  - AssessmentRepository::hydrateAssessmentSummaryFromDatabaseRecord and
    LibraryAssetResultBlobRepository::hydrateResultBlobFromRecord assigned
    \DateTime::createFromFormat()'s result straight into a non-null \DateTime
    (property / setCreationDate) — a missing or unparseable date column makes
    createFromFormat() return false, so hydrating such a row threw a TypeError.
    Guard the parse and keep the existing constructor-default date on failure.
  - ErrorCode::getErrorStringForErrorCode() tested code membership against
    ErrorCodeStatus::codeMap but read the value from ErrorCode::codeMap; it worked
    only because the two maps share keys today. Check self::codeMap.

  Known issues documented in TEST-PLAN.md for the upcoming source-typing pass
  (not changed here): Client::fromJSON() is dead/broken (unused), and several
  fromJSON/hydrators have type-coercion smells (string setters fed `?? 0`, a
  parent::fromJSON type reset, property/accessor type mismatches).

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
  - ResourceImporterService::importReports() read the report fields under the
    wrong keys (assessment / linkedGroup / id / name / data) while the export
    format and its test fixture — like the sibling importers — use underscore-
    prefixed keys (_assessment / _assessmentgroup / _id / _name / _data). Every
    report import therefore failed the linked-assessment/group lookup
    ("Failed to find assessment or assessment group"). Use the underscore keys.
    (Pre-existing since 0.9.0; surfaced by running the full phpunit suite against
    a seeded DB.)
  - AssessmentRepository::getAssessmentForAssignmentItem(): the query selected
    `ab1.status`, but this query's FROM aliases the tables item/assessment/
    assignment — there is no `ab1` alias here (that alias belongs to the other
    methods). So the query raised "Unknown column 'ab1.status'" (MySQL 1054)
    whenever an assessment was fetched for an assignment item. The status column
    is on the assessment-blob table, aliased `assessment` here → use
    assessment.status.
  - AssessmentAppointmentController::onServiceDelete() foreach'd the result of
    AssignmentRepository::getAssignmentsForAppointmentId(), which returns ?array
    (null when the appointment id is empty) — a foreach over null warns and skips
    the cleanup. Null-coalesce to [] so the delete-cleanup loop is null-safe.
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
    ClientSearchRepository $_repo. Round 3: specify iterable value types
    (array<mixed>, with |null where the native type is ?array) on ServerRestRequest's
    PSR-7 array accessors and the LibraryAssetBlob/LibraryAssetBlobResult DTO
    tag/result/answer arrays. Rounds 4a/4b and the follow-up extend this across
    the rest of the module — all Models, the FHIR resource services (matching the
    FhirServiceBase/ResourceServiceSearchTrait parent `@param array`), and the
    repositories/controllers/services — ELIMINATING the entire
    missingType.iterableValue category (0 remaining module-wide, was ~125).
    Values are array<mixed> (|null where the native type is ?array; the specific
    element type AssessmentSnippet[]/Assignment[]/SystemError[] where the backing
    property is typed), so no inference change — EXCEPT on the FhirServiceBase
    overrides, where a plain array<mixed> is wrong both ways: the parent
    loadSearchParameters() returns array<string, FhirSearchParameterDefinition>
    (array<mixed> is a wider, non-covariant return → method.childReturnType) and
    IResourceReadableService::getAll()'s parameter is mixed (array<mixed> is a
    narrower, non-contravariant param → method.childParameterType). Match the
    parent exactly on those: array<string, FhirSearchParameterDefinition> returns
    and a mixed getAll() parameter. (These nine covariance regressions were missed
    at first because the whole-module self-check was silently aborting on an
    undefined-sqlStatement bootstrap error — the OpenEMR root phpstan.neon.dist
    must be inherited from inside the OpenEMR tree so its relative scanFiles
    library/sql.inc.php resolves; running it through an out-of-tree merge config
    left the SQL globals undefined and the analysis incomplete.)
  - Regenerate the committed baseline from a full local environment (the OpenEMR
    8.4.0 worktree with the module installed under interface/modules/custom_modules,
    its nested vendor, and a seeded MariaDB): 2289 errors / 1691 entries, scoped to
    this module's src/ only, every path relative. Verified end to end — applying
    the baseline reports zero errors, and the DB-backed phpunit suite passes
    (8 tests, 40 assertions).
  - Baseline burn-down (round 5): specify the missing return and parameter types
    across the whole module — PHPDoc `@param`/`@return` tags only, no native
    signatures touched, so runtime behavior is byte-identical — ELIMINATING the
    entire missingType.return and missingType.parameter categories (0 remaining
    module-wide, was 263 + 228). Values are the type the body/usage implies
    (array<mixed> for arrays, the concrete class/scalar where unambiguous, void for
    non-returning methods, mixed at genuinely untyped DB/JSON/request boundaries);
    overrides match their parent to stay covariant (e.g. supportsCode()/
    insertOpenEMRRecord() against FhirServiceBase, the IRestController $id). Naming
    the types surfaced the latent type-friction they had hidden — callers passing
    mixed into the now-named slots (argument.type), a handful of return.type — which
    is recorded in the baseline as the next debt to burn down. Also fixed three real
    issues the typing exposed: AssignmentRepository::populateAssignmentsForClients
    declared `@return Assignment[]` but only mutates its argument and returns nothing
    (→ void); AssessmentReportRepository::getOne() and ClientRestController::
    sendMessageToClient() fell through to an implicit null under a non-void return
    (added explicit `return null;`); and Bootstrap's service-locator getters called
    methods on the `object` that Symfony's Container::get() returns (added
    `/** @var */` on each local so the concrete service type is known). Baseline is
    now 1619 errors / 1074 entries (was 2289 / 1691); applying it reports zero
    errors and the DB-backed phpunit suite still passes (8 tests, 40 assertions).
  - Baseline burn-down (round 6): argument.type. Reduce the class from 517 to 354
    (163 cleared) by narrowing at the SOURCE rather than silencing: `/** @var */`
    on json_decode/DB-row/request locals, tightening a few getter returns, (array)
    casts where the value is genuinely an array, and wrapping `json_encode()` in a
    PSR `createStream()` where it was being handed straight to `withBody()` (which
    needs a StreamInterface, not a string). Deliberately did NOT cast mixed values
    to scalars: this build's strict rules reject "Cannot cast mixed to int/string",
    so `(int)`/`(string)` on a mixed value only relabels argument.type as cast.int/
    cast.string — silencing, not fixing. The remaining mixed-at-boundary sites
    (DB/json/request values flowing into scalar params) stay baselined pending a
    deeper source-typing pass (typing DB rows as array<string, ?string>, etc.).
    Three latent bugs surfaced and fixed along the way: ClientRestController and
    MessageTemplateRestController called RestUtils::returnAccessDeniedResponse()/
    getErrorResponse() without the required leading SystemLogger argument (a
    TypeError under strict_types on every error path) — pass $this->logger to match
    the sibling call sites. Baseline is now 1420 errors / 924 entries (was 1619 /
    1074); applying it reports zero errors and the phpunit suite passes (8 tests,
    40 assertions).

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
