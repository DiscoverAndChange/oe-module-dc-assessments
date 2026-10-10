v0.12.32 Default the updated OAuth2 pages ON + fix/guard the provider EHR launch (no re-login)

  table.sql now defaults dac_assessments_oauth2_layout_override = '1' on fresh installs (via #IfNotRow,
  so it only sets the default when the global has no row -- it won't override an admin who turned it off),
  so new installs serve the branded SMART login / scope-authorize / patient-select pages.

  Fixes the in-EHR provider launch re-login bug: the SPA's admin authorize dropped the SMART `launch`
  parameter, so an in-EHR launch forced a second login. The SPA fix lives in assessments-angular
  (forward launch + iss); the recompiled public/frontend bundles are updated here.

  UAT: adds a 'provider ehr launch' test that logs the provider into OpenEMR, launches
  ehr-launch-client.php, and asserts NO second login + the admin app opens and shows the completed
  assessment. Also updates the provider-login spec helper for the (now default-on) tabbed login page --
  the provider "OpenEMR Login" button lives in a hidden #provider pane, so the test activates the
  "Provider Login" tab first. seed-assignment.php returns the provider client id. All 4 browser UAT
  tests green on the live 8.4 stack with the updated OAuth2 pages active.

v0.12.31 Two-client OAuth upgrade: trim the existing public client to patient-only scopes

  Upgrade pathway for installs created before the two-client split (v0.12.28), which registered a single
  PUBLIC patient client carrying a mixed patient+user scope set. The confidential provider client cannot
  be created in table.sql (its secret is encrypted with site-specific keys via the module's PHP), so it
  auto-registers on the first provider SPA/EHR access after upgrade (verified self-heal: deleting the
  provider client + globals and loading any SPA page re-registers it). What table.sql CAN do, added here:
  trim the existing public patient client down to patient-only scopes (idempotent; only touches a client
  still carrying user/*). SmartAppClientService now also registers new public clients with
  getSmartAppPatientScopes() (least privilege). Verified on the live 8.4 stack: existing public client
  trimmed (user/* removed), patient + provider UAT still green (3 tests), phpstan clean, unit 543 green.

v0.12.30 Provider-review works: admin SPA uses the confidential client via the token broker (full e2e green)

  Completes the 13-step scenario. The admin SPA now authenticates with the confidential provider client:
  the SPA runs the PKCE authorize with the provider client id (injected as adminClientId / adminScopes)
  and hands the code to the server-side broker (v0.12.29) for the secret-bearing token exchange, then
  builds the FHIR client from the brokered token (FhirService.authorizeUserAdmin/completeAdminAuth +
  getFhirClient/fhirClientReady branch on a dc-admin-oauth flag). SPA source change lives in the
  assessments-angular repo (openemr-integration); recompiled public/frontend bundles are updated here.

  Verified end to end on the live 8.4 stack: provider logs into the admin app (OpenEMR Login ->
  scope-authorize -> broker 200 -> reports/clients 200), opens the patient's client record, and sees the
  patient-submitted assessment marked complete with a View Report action. The browser UAT now runs all
  three: patient login, patient workflow (submit 201), and provider review -- 3 tests green with bounded
  teardown. phpstan clean, unit suite 543 green.

v0.12.29 Server-side provider token broker for the confidential admin client

  Adds the backend-for-frontend token broker that lets the browser admin app use the CONFIDENTIAL
  provider client without ever holding its secret. SmartAppClientService::exchangeProviderAuthorizationCode()
  completes the authorization_code exchange server-side (adds client_id + client_secret, POSTs to the
  OpenEMR token endpoint over loopback via Guzzle); public/backend/provider-token.php exposes it to the
  SPA (the SPA keeps the PKCE verifier, the server adds the secret). GlobalConfig gains
  getOAuthTokenUrl()/getOAuthTokenUrlInternal(). FrontendDispatchController now injects the provider
  client id (adminClientId) into the SPA page (frontend.html.twig data-admin-client-id + dacAppConfig).

  Validated end to end on the live 8.4 stack: a provider PKCE authorize with the confidential provider
  client yields a code, and the broker exchanges it for an access_token that INCLUDES user/clients.read
  (the scope the public patient client dropped). phpstan clean (Guzzle, not raw curl; GuzzleException
  catch), suite 543 green.

  Next: the admin SPA uses this broker (authorize with the provider client -> finalize via broker ->
  build the FHIR client), then the provider-review e2e.

v0.12.28 Register a confidential provider SMART client (two-client OAuth) + fix getBody() tests

  Foundation for provider-side review. OpenEMR only grants user/* scopes to CONFIDENTIAL clients
  (AuthorizationController: "system and user scopes are only allowed for confidential clients"), so a
  single public patient client dropped the provider's user/clients.read -> /api/reports/clients 401.
  SmartAppClientService now registers TWO clients: the existing PUBLIC patient client (standalone
  patient launch, patient scopes) and a new CONFIDENTIAL provider client (client_role=user,
  is_confidential=1, server-side secret, user/* scopes) for the in-EHR provider launch. GlobalConfig
  gains provider client id/secret storage + getSmartAppProviderScopes()/getSmartAppPatientScopes()
  (context-filtered). Bootstrap's EHR-launch menu now uses the provider client; the provisioner
  registers both. Validated on the live 8.4 stack: a confidential user client is granted user/clients.read
  and GET /api/reports/clients returns 200 (vs 401 for the public client).

  Also fixes two unit tests that the v0.12.26 ServerRestRequest::getBody() stream-wrap change had
  invalidated (they mocked getBody() as a PSR-7 stream; core actually returns the raw body STRING, so
  they now mock a string and assert getBody() wraps it). phpstan clean, suite 543 green, patient e2e
  still green.

  REMAINING (not in this version): the admin SPA must USE the provider client via the EHR launch
  (confidential auth-code exchange brokered server-side); until then the provider-review e2e stays
  blocked. Tracked in tests/Uat/Browser/README.md.

v0.12.27 Patient assessment submit works: SPA posts results to the portal route (rebuilt bundles)

  Completes the patient workflow. Rebuilt the Angular SPA so assessment results submit to the module's
  PORTAL route instead of the SMART FHIR client: assessment.service.ts saveAssessmentResult now calls
  this._dac$http.post("QuestionnaireResponse", ...) (HTTPService.generateUrl() routes patient requests
  to /apis/default/portal/ with the Bearer header) rather than client.create() on the FHIR base, which
  core denies for the patient role. Source change is in the assessments-angular repo
  (openemr-integration branch); the recompiled public/frontend bundles are updated here. Build notes
  (Node 17+ needs NODE_OPTIONS=--openssl-legacy-provider; the branch's dev-only `debug` module must be
  removed from app.module.ts to compile) are in tests/Uat/Browser/README.md.

  Verified end to end on the live 8.4 stack: login -> scope-authorize -> dashboard lists the assigned
  assessment -> Get started -> answer -> Submit -> POST /apis/default/portal/QuestionnaireResponse 201
  -> dashboard shows the "all of your assignments are complete" confirmation. The UAT submit spec now
  asserts the portal target + 201 + the confirmation. (Combined with v0.12.26's getBody() stream fix.)

v0.12.26 Fix ServerRestRequest::getBody() to return a PSR-7 stream (unblocks patient portal QR submit)

  Root-caused the assessment-submit 401 via the browser UAT: the compiled SPA posts the
  QuestionnaireResponse to the FHIR base, which OpenEMR core categorically blocks for the patient role
  (AuthorizationListener -> 401; the token carries patient/QuestionnaireResponse.write, so it is a role
  policy, not a scope gap). The module already registers the same patient-write route under the PORTAL
  base. Replaying the POST there surfaced a module bug: ServerRestRequest::getBody() declared
  `: StreamInterface` but returned core HttpRestRequest::getBody(), which returns the raw body STRING
  (Symfony getContent()) -> TypeError -> 500 on every module POST route that reads getBody() (the FHIR
  path never hit it because it 401s first). Fixed getBody() to wrap the string in a PSR-7 stream via
  ServiceContainer::getStreamFactory(). With the fix, replaying the patient QuestionnaireResponse to
  /apis/default/portal/QuestionnaireResponse returns 201 and persists the result. The remaining change
  to make the in-app Submit work is SPA-side (post to the portal base instead of the SMART FHIR client)
  and needs an Angular rebuild; the UAT submit spec asserts the current 401 until then. phpstan clean,
  529+ unit suite green.

v0.12.25 Browser UAT: assignment workflow (seed -> dashboard -> open/answer/submit) verified on live 8.4

  Extends the UAT tier to the assignment workflow. Adds tests/Uat/Browser/tools/seed-assignment.php:
  a container-side seeder that creates a patient with active portal credentials, an assessment (reusing
  the committed fixture blob so it has a real, answerable question), and an assignment of it to the
  patient -- via the module's own AssessmentRepository / AssignmentRepository. BrowserUatTestCase gains
  seedAssignedAssessment() (invokes the seeder over `docker exec`) and extends teardown to drop the
  seeded dac_AssessmentBlob rows (dropping referencing dac_AssignmentItem rows first). The Playwright
  spec now: logs in through the SMART/OAuth2 + scope-authorize consent flow, asserts the dashboard
  lists the assigned assessment, opens it (Get started -> /take/<id>), answers the radio question, and
  submits.

  DISCOVERED BLOCKER (documented, test asserts it): patient Submit POSTs the QuestionnaireResponse to
  the FHIR base (/apis/default/fhir/QuestionnaireResponse) and OpenEMR core's AuthorizationListener
  categorically denies patient-role FHIR writes -> HTTP 401 "Patient user role is not allowed to write
  FHIR resources" (the patient token DOES carry patient/QuestionnaireResponse.write -- it's a role
  policy, not a scope gap). The module also registers the patient-write route on the PORTAL base
  (/apis/default/portal/QuestionnaireResponse), which core allows, so the fix direction is submitting
  there. The submit spec asserts the 401 for now so it documents the real failure instead of hanging.
  Verified end to end on the live 8.4 stack with clean bounded teardown.

v0.12.24 Browser UAT: stack provisioner + patient-login spec verified end-to-end on a live 8.4 stack

  Hardens the Playwright UAT tier (v0.12.23) into something reproducible and proven. Adds
  tests/Uat/Browser/tools/provision-stack.php: a one-shot, idempotent provisioner (run as the web
  user inside the OpenEMR container) that enables the module (type=0 custom, not Laminas), runs
  table.sql via core SQLUpgradeService, ensures the stack prerequisites the SMART flow needs
  (rest_api/rest_fhir_api/rest_portal_api/rest_system_scopes_api, oauth_password_grant,
  portal_onsite_two_enable, enforce_signin_email=0, site_addr_oath), registers + enables the SMART
  client, and -- given baseurl=<public URL> -- rewrites the client redirect_uri to the public origin
  so OAuth2 authorize doesn't fail with invalid_client. The `patient login` spec now passes end to
  end against a live OpenEMR 8.4 dev stack (PHPUnit seed via PDO -> Playwright SMART/OAuth2 login ->
  SPA dashboard renders -> assert -> bounded teardown). @playwright/test pinned to 1.48.2 (last line
  supporting Node 18). README documents the full bring-up + provision + run recipe. The deeper
  assessment/review steps remain test.fixme()/TODO. Default `composer test` still unaffected.

v0.12.23 Scaffold the browser (Playwright) UAT tier for the patient SMART-app workflow

  New end-to-end test tier under tests/Uat/Browser, mirroring oe-module-ihi's conventions (opt-in
  flag, ordered preflight with markTestSkipped, bounded self-cleaning teardown, dedicated bootstrap,
  #[Group('browser')], a `composer uat:browser` script) -- but PHPUnit drives Playwright as a
  subprocess instead of Panther/Selenium. PHPUnit stays the entry point and reporter: BrowserUatTestCase
  does opt-in gating (DC_BROWSER_UAT=1), 4-stage preflight (flag -> Playwright installed -> app TCP
  reachable -> DB reachable + module active), raw-PDO patient seeding with ACTIVE portal credentials
  (portal_pwd_status=1), runs Playwright via proc_open, and parses its JSON report back into assertions
  (assertPlaywrightPassed). Teardown deletes only run-created rows (patient_data.id > baseline), child
  tables first. Isolated Node project in tests/Uat/Browser/playwright (own package.json/node_modules,
  not the module vendor). The `patient login` spec (login -> SPA dashboard renders) is wired end to
  end; the deeper steps (open assignment -> answer/submit -> provider review) are test.fixme() and the
  battery/assignment seeding is TODO, both pending authoring against a running stack. Default
  `composer test` is unaffected (the tier is outside the tests/Tests suite and self-skips).

v0.12.22 Patient SMART-login coverage: testability refactors + SmartAppClientService / FrontendDispatchController / OAuth2-override tests

  Builds on v0.12.21 (the credentials-invalid fix). Two behaviour-preserving testability refactors
  plus the coverage they unlock:
  - SmartAppClientService now constructor-injects its ClientRepository (default `new ClientRepository()`
    preserves prior behaviour; Bootstrap wiring unchanged), so client registration/enable can be
    tested without writing to oauth_clients. Also made isClientEnabled()'s guard rule-compliant
    (!empty -> !== false).
  - FrontendDispatchController's "client not enabled" branch now RETURNS a 500 ResponseInterface
    instead of die($body); the caller (public/frontend/index.php) already emits the returned response,
    so the error page still shows -- but the controller is now unit-testable and no longer halts the
    request with a 200.
  New tests (14): SmartAppClientServiceTest (5 -- existing-client short-circuit, first-time patient-
  client registration + enable + id persistence, isClientEnabled states), FrontendDispatchControllerTest
  (2 -- both not-enabled branches return 500 via an injected ArrayLoader twig), BootstrapOAuth2OverrideTest
  (4 -- login/patient-select overridden when the layout flag is on, untouched when off, error page never
  overridden), and PatientPortalLoginPreconditionTest (3, from v0.12.21). Suite 529 -> 543, phpstan clean.

v0.12.21 Fix SMART-app patient first-login ("credentials invalid") by forcing portal_force_credential_reset='1'

  The patient-facing SPA authenticates via OpenEMR's OAuth2 "portal-api" flow, which has no UI for
  the standard portal first-login credential-reset/verify step. With the core default global
  portal_force_credential_reset='0' (Allow), a newly-created patient is left unverified
  (patient_access_onsite.portal_pwd_status != 1 / portal_onetime set), so AuthUtils rejects them
  BEFORE checking the password -> "credentials invalid" on first login. table.sql now forces the
  global to '1' (Disable) on install/upgrade via an #IfNotRow2D-guarded REPLACE (the global has no
  DB row by default). create_portallogin.php then computes forced_reset_disable=1 and
  PatientAccessOnsiteService::saveCredentials() stores portal_pwd_status=1 for every new patient,
  so the first SMART login succeeds. Added PatientPortalLoginPreconditionTest (3 tests, DB-backed)
  pinning the core precondition the SMART flow depends on: same correct password is rejected while
  unverified and accepted once verified, and a wrong password is still rejected.
v0.12.20 PHPStan refactor pass 11: genuine correctness long-tail (baseline 394 -> 375)

  Fixes real bugs + dead code (not rule-compliance). AssignmentTaskFHIRResourceService used three
  classes with NO imports (QuestionnaireResponseService/QuestionnaireResponseOnSiteDocumentService/
  QueryUtils) -> resolved to a non-existent \Services\Task\ class, so the Task-complete path would
  fatal (class.notFound x5); the import fix then exposed a real arg bug (createDocument() was passed
  the decoded $questionnaire ARRAY as its $questionnaireName string param -> now $questionnaireTitle).
  Also: AssignmentEncounterController type-hinted Symfony Contracts EventDispatcherInterface (no
  addListener) instead of the Component one (method.notFound); CsrfUtils collect/verifyCsrfToken now
  require a SessionInterface (3 call sites passed too few args -> ArgumentCountError) -> pass the
  active session; buildQuestionnaireResponseHtml() 1-param but 2 call sites passed a stray separator;
  ->format() on nullable date getters -> ?->; removed 5 unreachable dead statements; collapsed an
  always-true == 'OperationOutcome' else-if.
  Left baselined (documented): injected-but-unread properties, GlobalConfig LOCAL_DEBUG dev-toggle,
  ServerRestRequest getRequestTarget/withRequestTarget (needs core/PSR decision), ClientRestController
  <1 pagination branch, APIProxyController dead proxy-fallback, SystemUser::fromJSON dynamic props,
  unused feature-methods (kept for later wiring), getResultId/setResultId-on-base-Assignment (model
  refactor). 529-test suite green, phpstan clean, baseline adds 0.

v0.12.19 PHPStan refactor pass 10: QueryUtils transactions -> inTransaction() (baseline 465 -> 394)

  Converted all 19 start/commit/rollbackTransaction blocks (57 deprecatedSqlFunction errors) to
  QueryUtils::inTransaction(fn) across 12 files. inTransaction() commits on success and rolls back
  + rethrows on Throwable, so each transactional body became a closure and the method's existing
  outer try/catch was kept to preserve behaviour -- REST controllers `return inTransaction(fn ...
  return $resp)` (AccessDenied/Exception catches unchanged; redundant commit-flag + finally-rollback
  removed), and the FHIR insert services + QuestionnaireAssignmentListener keep their swallow-and-
  recover catches so a failed completion still returns a graceful ProcessingResult / lets the QR save
  succeed. Validation/ACL/parse steps that preceded startTransaction stay outside the closure.
  Net -71 (the extra beyond 57 is the nested catch(\Exception) inside the old finally-rollbacks).
  529-test suite green (incl. completion-chain + ACL integration tests), phpstan clean, baseline adds 0.

v0.12.18 PHPStan refactor pass 9: empty() -> strict comparisons (baseline 638 -> 465)

  Largest bucket. Converted 174 of the module's 190 empty() uses to strict comparisons across
  ~35 files, per OpenEMR's empty.notAllowed rule. Per-operand idioms (level 10 forbids mixed casts
  and mixed-in-boolean): arrays -> === []/!== []; possibly-unset offsets -> isset()+!==''/!==[];
  ?string -> === null/=== ''; ints -> === 0; objects -> === null; string|false (json_encode/
  decrypt) -> is_string(); nullable method results captured in narrowing locals. In-scope domain
  done behavior-preservingly; the owner-approved out-of-scope files (ResourceImporterService,
  AssessmentAppointmentController, QuestionnaireAuditController, LibraryAsset*) included. A few
  dead/always-true guards exposed by the narrowing were cleaned up.
  Left as empty() + baselined (16): FHIR-element getter null-guards and shaped-array "always
  truthy" guards (mistyped-non-null in core / over-narrowed @var) -- converting them trips
  always-true/false, so they stay baselined (same rationale as pass 4). 529-test suite green,
  phpstan clean; baseline diff adds 0.

v0.12.17 PHPStan refactor pass 8: $GLOBALS -> OEGlobalsBag (baseline 655 -> 638)

  Superglobal bucket, $GLOBALS half: replaced all 17 `$GLOBALS['key']` / `$GLOBALS[self::CONST]`
  offset reads (forbiddenGlobalsAccess) across 8 files with OEGlobalsBag::getInstance()->get(...),
  keeping any `?? default`. css_header uses the typed getString() accessor (OEGlobalsBag's
  untypedGlobalGet rule requires typed getters for its registered string globals). Behaviour-
  preserving: OEGlobalsBag wraps the same OpenEMR globals. Baseline diff adds 0 / removes 8 entries
  (17 errors); 529-test suite green, phpstan clean.
  Deferred (still baselined, 18): the request-superglobal half ($_SERVER/$_POST/$_GET/$_REQUEST,
  forbiddenRequestGlobals) -- needs a threaded Symfony/HttpRest request object and most sites are
  in the out-of-scope appointment/calendar controller.

v0.12.16 PHPStan refactor pass 7: OpenEMR Psr17Factory instantiation sweep (baseline 665 -> 655)

  Mechanical bucket: replaced the 10 `new OpenEMR\Common\Http\Psr17Factory()` instantiations
  (forbiddenInstantiation) across EmptyRestController / FrontendDispatchController /
  QuestionnaireAuditController with ServiceContainer PSR-17 getters, per the rule's suggestion.
  Each local served both createResponse() and createStream(), so calls were inlined to their
  specific typed getter (ServiceContainer::getResponseFactory() / ::getStreamFactory()) and the
  local dropped. Behaviour-preserving (same container-registered factories; identical semantics).
  Only the OpenEMR Psr17Factory is flagged -- the Nyholm\Psr7 one used in other controllers is not
  touched. Baseline diff adds 0 / removes 3 entries (10 errors); 529-test suite green, phpstan clean.

v0.12.15 PHPStan refactor pass 6: SystemLogger instantiation sweep (baseline 688 -> 665)

  Mechanical module-wide bucket: replaced every `new SystemLogger()` (23 forbiddenInstantiation
  errors across 17 src files) with `ServiceContainer::getLogger()`, per the rule's own suggestion.
  The handful of slots that receive the returned LoggerInterface were retyped SystemLogger ->
  LoggerInterface (AssessmentRepository + LibraryAssetBlobRepository constructors; the logger
  properties in ClientSearchRepository/AssessmentGroupRestController/AssessmentRestController/
  Bootstrap; RestUtils::getErrorResponse()'s $logger param). Behaviour-preserving: SystemLogger IS
  what ServiceContainer::getLogger() returns, only PSR ->error()/->debug() are called, and
  SystemLogger implements LoggerInterface so all callers (including the test suite's
  `new SystemLogger()`) stay compatible. DI-injected `private SystemLogger $logger` constructor
  params were left untouched (autowired via Bootstrap's SystemLogger::class alias; never flagged).
  Baseline diff adds 0 entries / removes 13; 529-test suite green, phpstan clean.

v0.12.14 PHPStan refactor pass 5: AssessmentResponseBlob FHIR service (baseline 706 -> 688)

  Fifth burn-down pass over the assessment-response-blob FHIR insert/parse service (exercised by
  its parse + insert tests). 529-test suite green, phpstan clean. Dead-code / correctness fixes:
  - insertOpenEmrRecord had a duplicated, unreachable item-validation block (the first checks
    throw) -- removed.
  - validateCreateAccessAndReturnClient's `!empty($userId && !AclMain::aclCheckCore(...))` wrapped
    the whole && inside empty() (misplaced paren); rewrote as explicit && (same result, clear intent).
  - removed a dead unreachable provenance stub + its now-unused import.
  Plus getLogger()?->error() (nullable getter), typed post-validation $openEmrRecord shape +
  authUserID/client-uuid/client-pid locals (fixing the offset + arg-type errors), and empty() ->
  strict comparisons.
  Left baselined (13): the FHIR element setter arg types (core types them as FHIR elements but
  they store/return raw scalars -- the parse test asserts getName() === 'computable', so wrapping
  would break behavior), the parseFhirResource array|null childReturnType (null-vs-[] contract),
  and the QueryUtils transaction deprecation + its error-swallowing catch(\Exception).

v0.12.13 PHPStan refactor pass 4: QuestionnaireResponseForm FHIR mapping (baseline 731 -> 706)

  Fourth burn-down pass over the QR-response FHIR read/insert mapping (exercised by the parse
  tests). 529-test suite green, phpstan clean. parseOpenEMRRecord now wraps scalars in their
  FHIR element types before the setters -- new FHIRId/FHIRInstant/FHIRDateTime (scalar) and
  new FHIRQuestionnaireResponseStatus(['value' => ...]) (array form required); createRelativeReference()
  results (untyped -> mixed in core) captured in typed FHIRReference locals; DateTime::createFromFormat()
  false-return guarded before ->format(). Plus empty() -> strict comparisons, new SystemLogger() ->
  ServiceContainer::getLogger(), a widened createProvenanceResource @param (childParameterType), and
  a removed unreachable provenance stub.
  Left baselined (16): the parseFhirResource getter null-guards and three set*(null) field-clears --
  OpenEMR's FHIR element getters/setters are typed non-null in core yet are genuinely nullable at
  runtime (optional QuestionnaireResponse id/subject/encounter/source), so removing the guards would
  reintroduce a real null-fatal.

v0.12.12 PHPStan refactor pass 3: QR REST controllers + QR FHIR services (baseline 756 -> 731)

  Third burn-down pass over the questionnaire read/create REST controllers and the two QR FHIR
  delegating services (all exercised by the QR controller-create + FHIR tests). 529-test suite
  green, phpstan clean. Dead-code / correctness fixes:
  - Removed a dead private getOne() from both QR REST controllers (the public one() is used).
  - QuestionnaireResponseRestController: dropped a stray production `use PHPUnit\...\
    InvalidArgumentException`, collapsed an always-true == 'OperationOutcome' branch to else,
    made the constructor's resource service non-nullable (DI + the test both inject it).
  - QuestionnaireResponseFHIRResourceService: removed an unused $service property and two
    always-true `instanceof GenericEvent` guards.
  - QuestionnaireFHIRResourceService: getServiceForCode() can return null but the @var asserted
    non-null (isset always true) -> retyped + !== null; removed an unreachable provenance stub.
  Plus empty() -> strict comparisons, new SystemLogger() -> ServiceContainer::getLogger(),
  createBundle() result typed FHIRBundle, and $GLOBALS/$_SERVER hoisted to typed locals (clearing
  a mixed-concat binaryOp.invalid).
  Deferred (still baselined): the $GLOBALS/$_SERVER superglobal bucket, catch(\Exception), an
  injected-but-unread logger, and one inherited FHIR getAll() contravariance quirk from core.

v0.12.11 PHPStan refactor pass 2: QR listeners + client/user repos (baseline 784 -> 756)

  Second burn-down pass over the questionnaire-completion glue + client/user repositories
  (all exercised by the completion-chain/listener tests). 529-test suite green, phpstan clean.
  Dead-code / correctness fixes:
  - QuestionnaireResponseRestListener::dispatchFHIRSearchEvent had unreachable "else" error
    branches: getAll() always returns a ProcessingResult (errors are carried inside it, never
    via a null return). Removed the dead branches; addProcessingResult already merges errors.
  - QuestionnaireAssignmentListener returned a value from a void listener method (return.void);
    the "first incomplete item wins" exit is now a break, leaving the commit/finally unchanged.
  - ClientRepository's @var over-narrowed nullable/possibly-empty getListOption()/getGroup()
    results to a required-key shape, making the empty() guards dead; retyped + null/=== [] checks.
  Plus empty() -> strict comparisons, new SystemLogger() -> ServiceContainer::getLogger(), and a
  string coercion for createDocument's category id.
  Deferred (still baselined): QueryUtils start/commit/rollbackTransaction() deprecation + its
  error-swallowing catch(\Exception).

v0.12.10 PHPStan refactor: core assignment/client domain (baseline 846 -> 784)

  First phpstan burn-down pass, now that the completion-chain/fixture tests provide a safety
  net. Scoped to the in-scope core domain (patients/clients/assignments); the 529-test DB-backed
  suite stays green and phpstan stays clean against the regenerated baseline.
  Fixes real latent bugs surfaced by the analysis:
  - AssignmentRepository::createAssignmentItemFromObject / updateCompletedAssignmentItem built
    their SQL/params only inside if/elseif chains with no final else -> an unexpected item
    subtype hit an undefined variable + null SQL. Now throw InvalidArgumentException instead.
  - AssignmentRepository called ->format() directly on ?DateTime getters (fatal on null) and
    passed DateTime::createFromFormat()'s DateTime|false straight into setDate*(DateTime);
    added a requireDateAssigned() guard and a createFromFormat false-guard.
  - Client::fromJSON was dead + broken (array_merge of an object; returned an array) -> removed.
  - AssignmentCompleter::getAssignmentForItem (dead private method) called a nonexistent
    AssignmentRepository method -> removed.
  - ClientSearchRepository logged ProcessingResult::getErrors() (undefined) -> getInternalErrors().
  Plus behavior-preserving rule compliance: empty() -> strict comparisons (no mixed casts, per
  level-10), new SystemLogger() -> ServiceContainer::getLogger() where touched.
  Deferred to dedicated passes (still baselined): the SystemLogger-instantiation and
  catch(\Exception) buckets, injected-but-unread properties, and ClientRepository's
  over-narrowed @var sites.

v0.12.9 Integration coverage for the questionnaire -> assignment-completion pipeline (batch 6)

  Covers the live server-side flow when the patient SPA submits a completed assessment/
  questionnaire, scoped to patients/users/questionnaires/assignments (encounter-charting and
  library-asset results are out of scope; their heavy collaborators are mocked).
  - New test traits: tests/Tests/Support/AclIntegration.php (installs the default ACL tree
    once + logs in the seeded admin so AclMain passes) and AssignmentFixture.php (patient
    pid==id, published assessment, saved assignment + AssignedAssessment item, cleanup).
  - AssessmentResponseBlobFHIRResourceService::insertOpenEmrRecord: success persists the
    result blob + marks the assignment complete (mocked completer), invalid payload ->
    validation errors, unknown assignment item -> clean internal error.
  - QuestionnaireResponseRestController::create: Prefer/returnType handling + the 400
    (bad body) and 500 (service error) paths (resourceService mocked).
  - QuestionnaireResponseRestListener (insert/search routing) and QuestionnaireAssignmentListener
    (guard + no-match branches).
  - QuestionnaireResponseOnSiteDocumentService::createDocument: proves PDF generation still
    works end to end -- flattens the response to HTML, runs PatientPortalPDFDocumentCreator,
    and asserts a stored \Document (application/pdf) whose bytes start with "%PDF".
  - Full completion chain: a saved QuestionnaireResponse (ServiceSaveEvent) -> Questionnaire
    AssignmentListener -> PDF generation -> the questionnaire assignment item is marked
    complete. AssignmentRepository's onsite-portal-activity (audit) service is now an optional
    injected dependency (defaults to a real instance, so existing callers are unaffected), so
    the completion UPDATE is exercised with the audit service mocked.

  Bug fix (with regression test): QuestionnaireResponseRestListener::dispatchFHIRInsertEvent
  used $extension[0] after array_filter() (which preserves keys), so a QuestionnaireResponse
  whose DAC extension was not first would silently fail to route to the insert handler. Now
  reindexes with array_values() before taking the first match.

  Coverage ~54% -> ~56% lines; PHPStan-flagged errors in test-exercised methods 58% -> 64%.
  Module PHPStan clean. Latent issues documented (not fixed) in TEST-PLAN: dead search-error
  branches + an unused PHPUnit import in the QR listener/controller; QuestionnaireAssignment
  Listener missing strict_types / first-item-only completion; a benign Array-to-string warning
  at RestUtils.php:162.

v0.12.8 Fix SMART-app crash on assessment-users (NULL username address-book entries)

  Launching the "Patient Portal Assignments" SMART app failed on GET /api/assessment-users/:uuid
  (SystemUserRestController::one -> SystemUserRepository::getUsers) with
  "SystemUser::__construct(): Argument #2 (\$username) must be of type string, null given".

  Cause: OpenEMR's users table also holds non-login "address book" entries (external/referring
  providers) whose username is NULL. getUsers() iterated every user and fed that NULL into the
  non-null SystemUser::\$username. Fix: getUsers() skips username-less rows (they are not
  assessment system users) and hydrateUser() coalesces defensively; the username-related type
  annotations are corrected to ?string.

  Regression test: SystemUserRepositoryAddressBookTest seeds a real login user plus a NULL-
  username address-book user and asserts getUsers()/one() no longer crash and exclude the
  non-login entry. (Integration-style: seed -> run -> clean up.)

v0.12.7 Integration tests for ACL-gated write paths (pre-phpstan coverage build-out)

  Test-only change (no src changes). Before the planned aggressive PHPStan refactor, this
  builds DB-backed integration coverage for the controller write actions that were previously
  untestable because the bare test DB grants no ACL.
  - New tests/Tests/Support/AclIntegration.php trait: installs OpenEMR's default ACL tree once
    (idempotent) with the seeded user in the Administrators group and logs that user into the
    session, so AclMain::aclCheckCore() passes and the controllers' create/update bodies run.
  - Integration tests (seed -> run -> clean up, phptest% prefix): AssessmentReportRestController,
    AssessmentGroupRestController, AssessmentRestController, ClientRestController write actions.
  - Plus QuestionnaireResponseFormFHIRResourceService::parseOpenEMRRecord (read direction) and
    non-ACL service/repo methods (LibraryAssetResultBlobRepository save/search/saveTags,
    QuestionnairePortalTask search guards).
  Suite 478 -> 507 tests; line coverage ~47% -> ~54%; PHPStan-flagged errors in test-exercised
  methods rose from 42% to 58%. Module PHPStan still clean.

  Latent bugs surfaced (documented in TEST-PLAN, deliberately NOT fixed here): the
  QuestionnairePortalTask getTaskDataForTemplates $docMap keying bug; ClientRestController::
  sendMessageToClient returning null on success and lacking an ACL check.

v0.12.6 Provider audit + repo-branch coverage (Scope B batch 4); fix appointment-id lookup crash

  Coverage raised ~45% -> ~47% lines (453 -> 478 tests, phpstan clean):
  - QuestionnaireAuditController: dispatch routing, the view guards (missing recordId / no
    matching audit -> 404), and the dispatch error path (-> 500). The chart/view happy paths
    (encounter-form save, audit render) write to the DB / render real templates and are left
    to integration coverage.
  - ClientRepository happy paths: addGroupAssignmentToClient (group + its assessments),
    addAssignmentToClient (resolves a published assessment by uid), removeAssignmentFromClient.
  - AssignmentRepository remaining branches: audit-id item lookup, encounter reads, the
    appointment-id guard/no-op paths, and saving an AssignedAssessmentGroup with child items.

  Bug fix (exposed by the new AssignmentRepository branch test):
  - getAssignmentsForAppointmentId() passed the int $pc_eid into TokenSearchField, which
    requires a string -> "Token value must be a valid string" on every non-zero id. The
    provider appointment-render path (AssessmentAppointmentController) therefore crashed for
    any real appointment. Cast the id to string.

  Deferred (integration-level, documented in TEST-PLAN): the FHIR services' insert()/update()
  completed DB paths (the Task update() completed path is on the now-deprecated task.update
  route), createClientAssignmentForProfile / AssignedQuestionnaire save (document-template /
  core questionnaire fixtures), and the real appointment-linked read paths (calendar-event
  fixtures).

v0.12.5 FHIR-layer + Bootstrap test coverage (Scope B batch 3); fix uncaught search exception

  Coverage raised ~38% -> ~45% lines (409 -> 453 tests, phpstan clean):
  - FHIR delegation services (Questionnaire/QuestionnaireResponse/Task): search-param maps,
    code-based delegation, event short-circuit, createProvenance type guards, Task
    parseOpenEMRRecord.
  - Leaf FHIR services (Assessment, QuestionnaireForm, AssessmentResponseBlob,
    LibraryAssetResultBlob): supportsCode + parseOpenEMRRecord/parseFhirResource mapping.
  - Task sub-services (AssignmentTask, QuestionnairePortalTask): supportsCode,
    parseOpenEMRRecord FHIRTask build, and the update() guard/clean-fail branches.
  - Bootstrap: the DI container compiles, wires the public controllers, and
    subscribeToEvents() registers its listeners.

  Bug fix (with regression test):
  - QuestionnaireFHIRResourceService / TaskFHIRResourceService caught SearchFieldException
    without importing it, so the catch named a nonexistent module-namespaced class and the
    real OpenEMR\Services\Search\SearchFieldException escaped uncaught (an unsupported
    questionnaire-code/code -> uncaught 500 instead of validation messages). Added the import
    so getAll() surfaces it as validation messages.

  Documented, not fixed (future hardening): the Task services' update() completed-path reads
  getOutput()[0] without an existence check, and several parseOpenEMRRecord methods read
  required record keys by direct offset without isset guards (undefined-key warnings on
  malformed input).

v0.12.4 More latent bug fixes; raise test coverage ~27% -> ~38% (Scope B batch 2)

  Honest fixes (each with a regression test; module phpstan stays clean), several on
  LIVE routes that were crashing:
  - LibraryAssetResultBlobRepository::getDecryptedAssetResultBlob: the query aliased
    patient_data.pid as patient_pid but joined ON pd.pid (an unknown column), so EVERY
    call failed with a SQL error -- the live library-asset-results.one route (the patient
    SPA's getAssetResult) always 500'd. Also `return $results[0]` on an empty result raised
    an undefined-key warning (escalated to a 500) for a missing id. Fixed the join column
    and return null for no match (controller -> 404).
  - ServerRestRequest::getUri(): declared a UriInterface return but returned the raw
    HttpRestRequest string, so it TypeErrored whenever called -- e.g. the live
    assessment-users route (SystemUserRestController::list -> getUri()->getQuery()). Now
    wraps the string in a PSR-7 Uri.
  - AssessmentReportRestController::one(): returned 200 with a "null" body for an unknown
    id (the trailing getNotFoundResponse() was unreachable); now returns 404.

  Test coverage raised ~27% -> ~38% lines (349 -> 409 tests): REST controllers (Empty,
  Announcement, Token, Tag, SystemUser, Assessment, AssessmentGroup, AssessmentReport,
  MessageTemplate, LibraryAssetResult) and DB-backed repository CRUD/lifecycle
  (AssignmentRepository, AssessmentReportRepository, AssessmentResultRepository,
  ClientSearchRepository, LibraryAssetResultBlobRepository join path).

v0.12.3 Fix latent bugs surfaced by the audit; raise test coverage

  Honest fixes (no cast-to-silence; phpstan stays clean), each with a regression test:
  - QuestionnaireResponseFormFHIRResourceService::parseFhirResource: two operator-
    precedence bugs (`!empty($ref['type']) == 'Patient'` / `== 'Practitioner'` are always
    true, so subject/source type was never actually checked) plus an Encounter block that
    wrote into a throwaway variable using the response's own uuid, silently dropping a
    submitted Encounter reference.
  - Assignment::fromJSON: an absent "type" key no longer resets the type to "Assessment"
    (which had flipped isGroupType() false for AssignedAssessmentGroup/TemplateProfile).
  - AssignedQuestionnaire::fromJSON: hydrates resultId/documentId/documentTemplateId for a
    symmetric round trip and preserves the payload's dateCompleted against setResultId().
  - AssignmentRepository::hydrateAssignedLibraryAssetFromRecord: populate dates after
    setResultId so the parsed date_completed is not clobbered; tolerate missing keys.
  - AssessmentResultRepository::hydrateRecordsFromResult: normalize a non-array result_data
    payload; null-coalesce the assignmentitem_id/date meta keys.
  - LibraryAssetBlobResultDTO::fromDTO: parse an ISO-string creationDate (was a TypeError).
  - ClientSearchQueryDTO: default the typed properties so isEmpty() is safe before populate.
  - RestUtils::getResponseForProcessingResult: the internal-errors branch never set the
    status var, so the response builder hit an undefined variable; now returns 500.
  - TagRepository::getTagsForAssetIds: ids that all fail the positive-int filter no longer
    build an invalid "IN ()" clause; returns [] without querying.
  - ResourceImporterService::importReports: the real export (DiscoverAndChangeResources.json)
    writes reports with the SPA key convention (id/name/data/linkedGroup/linkedAssessments[])
    rather than the underscore form (_id/_name/_data/_assessment/_assessmentgroup). Every
    report there links to a group via linkedGroup, so the importer hit "Failed to find
    assessment or assessment group" and all report imports failed. importReports() now
    normalizes both conventions.

  Test coverage raised ~18% -> ~27% lines (177 -> 349 tests): pure-unit tests for the
  validators, Utils (RestUtils/FhirObjectDenormalizer), PaginatedResultsService,
  HTTPResponseUtils, AssignmentSerializer, Role/Capability/ServerRestRequest/GlobalConfig,
  the DTO/model fixes above, plus DB-backed/mocked repository tests (Tag/Token/
  MessageTemplate/Client).

v0.12.2 Deprecate REST/FHIR routes unused by the patient SPA

  Document-only change (no behavior change; all routes stay wired). An audit of the
  patient SPA (reconstructed from the public/frontend source maps) cross-referenced every
  request the frontend issues against APIProxyController::API_MAPPINGS. 7 of 39 routes are
  never called by the shipped SPA; each is marked @deprecated on both the API_MAPPINGS entry
  and the controller method, pointing at the TEST-PLAN.md "SPA route audit" record:
  - task.one / task.update (Tasks loaded only via search; client.update<Task> commented out)
  - clients.one (ClientService reads FHIR Patient/:id instead)
  - library-assets.list / library-assets.one (assets read via FHIR Questionnaire search)
  - library-asset-results.create (saveAssetResult POSTs a FHIR QuestionnaireResponse)
  - assessment-results.create (results created server-side via the QR save listener)
  Deletion deferred to a future release; task.one is retained as a standard FHIR read for
  external SMART clients.

v0.12.1 Fix latent runtime bugs surfaced by the source-typing pass

  Fix the clear, safe runtime bugs the v0.12.0 type analysis exposed:
  - TagRestController::list() referenced an undefined $this->logger on its DB-error
    path (fatal when listTags() throws); log via a SystemLogger instance (the
    module's ad-hoc logging idiom).
  - AssessmentReportRepository::getAll() referenced an undefined $this->logger in a
    catch that also caught an unqualified `Exception` (resolved to a non-existent
    class in this namespace); use a SystemLogger instance and `\Exception`.
  - AssessmentGroupRestController::createAssessmentGroupsFromEntities() used `return;`
    inside its inner loop when an AssessmentGroupAssessmentBlob had no blob — aborting
    the whole method and dropping ALL groups (returning null); `continue;` to skip just
    the malformed row. Return type tightened to AssessmentGroup[] (no longer nullable).
  - Assignment::fromJSON() defaulted a missing `id` to int 0 into the string setId()
    (TypeError under strict_types); default to ''.
  - ClientRepository: setAssessmentId() received the possibly-null result of
    getMostRecentAssessmentIdForUid() (TypeError when a uid has no published
    assessment); throw a clear InvalidArgumentException instead.
  - AssignmentTaskFHIRResourceService::update(): the guard threw whenever EITHER the
    assignment OR the item lookup was null (normally exactly one is), and the item
    branch called a never-defined updateAssignmentItem() (fatal undefined-method).
    Restructure so an assignment-item resource id fails cleanly ("not yet supported"),
    an assignment id routes to updateAssignment(), and only a wholly-unknown id errors.

  Still open (need author decisions; recorded in TEST-PLAN.md, NOT changed here): the
  FHIR Task item-update feature itself (updateAssignmentItem), QuestionnaireResponse
  FormFHIRResourceService's half-built encounter/source linkage (dead stores into an
  undefined var), saveLibraryAssetResultBlob() silently dropping two caller args, and
  the dead/broken Client::fromJSON(). APIProxyController's proxy methods are dead code
  (only its API_MAPPINGS constant is still used).

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
