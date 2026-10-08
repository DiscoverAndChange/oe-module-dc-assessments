# Test coverage plan & tracker

Living tracker for building out the module's automated test suite. Goal: a safety
net strong enough to refactor confidently (immediately: the PHPStan "source-typing"
pass that threads real types through DB rows / json_decode / request payloads), and
durable enough to keep the module in sync with rapid OpenEMR core changes.

Keep this file updated as tests land — it is the hand-off record across sessions.

## Why (context)
- The module is thinly tested (3 test files vs 96 src files / ~14.6k LOC).
- Most port bugs so far were **runtime** bugs PHPStan can't catch (dispatch TypeError,
  getBodyAsJson on a resource, empty FHIR bundle, `ab1.status`, missing logger args).
  Integration/characterization tests are the right net for those.
- The source-typing pass is ~90% zero-runtime PHPDoc; the risk lives in the minority
  (casts/guards/logic). Tests target that minority + lock in current behavior.

## Environment / how to run
- DB-backed suite (the working harness): `bash <scratchpad>/fullsuite.sh`
  - Requires the `oe-test-db` MariaDB container (root/root, port 3310, db `openemr`,
    OpenEMR schema + `dac_` tables + seeded `users` id=1).
  - Syncs `oe-module-dc-assessments/src` + `tests` into the OpenEMR worktree at
    `~/projects/openemr-wt-file-manifest/interface/modules/custom_modules/` and runs phpunit.
- Committed entry point: `composer test` (runs phpunit from the OpenEMR root against
  `phpunit.xml`). See [[phpstan-baseline-regen]] for the phpstan side.
- Conventions: namespace `OpenEMR\Modules\DiscoverAndChange\Assessments\Tests`,
  extend `PHPUnit\Framework\TestCase`, fixtures in `tests/data/`, DB rows cleaned up
  in `tearDown()` by a `phptest%` prefix (see `ResourceImporterServiceTest`).

## Coverage reports (one-time setup — needs sudo)
No coverage driver is installed. Install PCOV (faster than Xdebug, no suite slowdown):
```
! sudo apt-get update && sudo apt-get install -y php-pcov
```
Then generate a report (PCOV is off by default, enable per-run). **Gotcha:** PCOV
only instruments files under `pcov.directory` — you MUST point it at the module
`src/` or you get a misleading 0%:
```
MOD=interface/modules/custom_modules/oe-module-dc-assessments
cd ~/projects/openemr-wt-file-manifest \
 && php -d pcov.enabled=1 -d pcov.directory="$MOD/src" vendor/bin/phpunit \
      --configuration="$MOD/phpunit.xml" \
      --coverage-text --coverage-html "$MOD/coverage-html"
```
`phpunit.xml` declares `<source>` = `src/`, so the report is scoped to the module.
Treat it as a **map to prioritize**, not a target — line coverage ≠ assertion quality.
(Local dev here uses the `fullsuite.sh` harness + an absolute-path coverage config
because the committed bootstrap doesn't load the module's nested vendor; see session
scratchpad `phpunit-coverage.xml`.)

Coverage baseline (2026-10-08): **9.3%** line coverage (486 / 5243 statements),
12 files touched. Highest existing coverage: LibraryAssetBlobDTO 94%, Assignment 75%,
ResourceImporterService 70%, AssessmentGroupService 66%. Everything else is at or
near 0% — see the biggest zero-coverage targets (by statement count): Assignment
Repository (350), Bootstrap (263), AssessmentAppointmentController (176),
QuestionnaireAuditController (156), the FHIR form services (~150 each), ClientRest
Controller (151), AssessmentGroupRestController (146).

## Strategy: order by (change-risk × path-criticality)
1. **Pure-unit hydration** (no DB) — the exact code source-typing rewrites: array→object.
2. **Repository hydration** — `hydrate*FromRecord` with crafted row arrays (no/low DB).
3. **DB-backed integration** — CRUD + importers against `oe-test-db`.
4. **FHIR/service integration** — endpoint/delegation behavior (hardest; needs harness).

## Target checklist
### P1 — pure-unit hydration (no DB)  — DONE (coverage of these now ~100%)
- [x] `Models/Assignment` — fromJSON + jsonSerialize round-trip, type validation, items
- [x] `Models/AssignedAssessment`, `AssignedQuestionnaire`, `AssignedLibraryAsset`,
      `AssignedAssessmentGroup`, `AssignedTemplateProfile` — fromJSON (incl. parent call)
- [x] `Models/Client` — getDisplayName, addAssignment, sortAssignmentsByDateAssigned, jsonSerialize
- [x] `Models/SystemUser` (role bounds, caps), `AssessmentSummary`, `AssessmentSnippet`, `AssessmentGroup`
- [~] `DTO/LibraryAssetBlobDTO` — covered incidentally (~94%); add a dedicated test later
- [x] `DTO/LibraryAssetBlobResultDTO` — fromDTO + generateId + jsonSerialize
- [x] `DTO/ClientSearchQueryDTO` — populateFromRequest, isEmpty (note: no isValid() exists)
- [x] `Models/SystemError`, `ErrorCode`/`ErrorCodeStatus` — code→status/string mapping
### P2 — repository hydration (crafted row arrays via reflection) — DONE
- [x] `AssignmentRepository` — all hydrators (hydrateItemFromRecord routing + the five
      Assigned* leaf mappers + hydrateDocumentTemplateProfile + populateDatesForAssignment)
- [x] `AssessmentRepository::hydrateAssessmentSummaryFromDatabaseRecord` + getAssessmentSummaryFromRecords
- [x] `AssessmentResultRepository::hydrateRecordsFromResult`
- [x] `SystemUserRepository::hydrateUser` (runs under harness ACL; role asserted loosely)
- [x] `LibraryAssetResultBlobRepository::hydrateResultBlobFromRecord` (no-decrypt path;
      decrypt branch deferred to P3 — needs encryption keys)
- [~] `LibraryAssetBlobRepository` hydration only covered incidentally (~36%); dedicated test later
### P3 — DB-backed integration (oe-test-db)
- [ ] `ResourceImporterService` — extend beyond the current happy paths
- [ ] `AssignmentRepository` — create / get / complete lifecycle
- [ ] `AssessmentReportRepository` — getOne/getAll/create/update (fall-through → null)
- [ ] `AssessmentResultRepository`
### P4 — FHIR / service integration
- [ ] `QuestionnaireFHIRResourceService` / `TaskFHIRResourceService` — getAll/getOne delegation
- [ ] `AssignmentCompleter` — completion → notification/PDF flow
- [ ] `parseOpenEMRRecord` on the FHIR services

## Findings / latent bugs surfaced by tests
Candidates for the source-typing pass / follow-up fixes (tests characterize the
SAFE path; none of these were "fixed" while writing tests):
- **`Models/Client::fromJSON()` is dead** — `array_merge($client, (array)$obj)` with
  `$client` a Client *object* throws `TypeError` on every call. No working path.
  Confirmed UNUSED: grep of src/, public/, moduleConfig.php finds no caller (only
  AssignmentSerializer calls Assignment/AssignedX::fromJSON), so no production impact —
  it's dead code. Rewrite or remove during the source-typing pass; test is
  `markTestIncomplete` until then.
- [FIXED v0.11.2] **`Models/SystemUser::jsonSerialize()` crashed on a fresh object** — `$_companyName`
  has no default/initializer, so serialize-before-setCompanyName() throws. Give it `''`.
- `DTO/ClientSearchQueryDTO` — the 5 typed properties have no defaults, so `isEmpty()`
  before `populateFromRequest()` throws "must not be accessed before initialization".
- [FIXED v0.11.2] `Models/ErrorCode::getErrorStringForErrorCode()` checked membership against the WRONG
  map (`ErrorCodeStatus::codeMap`) then reads `ErrorCode::codeMap` — works only because
  the two maps share keys today; silently misbehaves if they diverge.
- `DTO/LibraryAssetBlobResultDTO::fromDTO()` — `setCreationDate($data['creationDate']
  ?? new DateTime())` into a non-null `\DateTime` setter: a real ISO *string* (the
  realistic case) is never parsed and TypeErrors; only pre-hydrated DateTime works.
  Also `jsonSerialize()` drops assetId/assignmentItemId/clientId (asymmetric round-trip).
- `parent::fromJSON` resets `type` to `"Assessment"` when the input lacks a `type` key —
  silently flips `isGroupType()` false for AssignedAssessmentGroup/AssignedTemplateProfile.
- `AssignedQuestionnaire::fromJSON` ignores resultId/documentId/documentTemplateId even
  when present (only questionnaireId is hydrated).
- String setters fed `?? 0`: `Assignment::fromJSON` setId, `AssignedQuestionnaire`
  questionnaireId, `AssignedTemplateProfile` profileId — a missing key stores "0"/0
  (works only in PHP coercive mode; TypeError under strict_types).
- Type mismatches: `AssessmentGroup::getId(): int` vs `int|string` property/setter;
  `AssignedAssessment` `string $assessmentId` vs `setAssessmentId(int)`/`getAssessmentId(): int`.
- Several Models read required typed properties in `jsonSerialize()` with no defaults —
  serialize-before-hydrate throws (uninitialized typed property).
- (P2) `AssignmentRepository::hydrateAssignedLibraryAssetFromRecord` populates dates then
  calls `setResultId()` last; `AssignedLibraryAsset::setResultId(non-null)` resets
  `dateCompleted` to now, clobbering the record's parsed `date_completed`. Masked in the
  full path by a trailing re-population, but wrong on a direct call. Order-of-operations bug.
- [FIXED v0.11.2] (P2) `createFromFormat(...)` returned `false` on an absent/invalid date and is assigned to
  a NON-NULL typed `\DateTime` → `TypeError`: `AssessmentRepository::hydrateAssessmentSummary
  FromDatabaseRecord` (`$result->date`) and `LibraryAssetResultBlobRepository::hydrateResult
  BlobFromRecord` (`setCreationDate`). Nullable/absent date column into a non-null property.
- (P2) `AssessmentResultRepository::hydrateRecordsFromResult` reads `assignmentitem_id`/`date`
  with no null-coalesce (undefined-key warning on absent keys) and assumes `json_decode` of
  `result_data` yields an associative array (a scalar/list payload would break the merge).
- (P2) `(int) $record['asset_id']` casts a null asset_id to 0 (no default protection).

## Phase 2: the source-typing pass — DONE (v0.12.0)
Result: baseline 1418 -> 882 errors / 523 entries. Container-mixed essentially gone
(offsetAccess 212->2, foreach 22->0, invalidOffset 30->0), argument.type 353->192,
return.type 49->10; zero cast.* introduced; 176 tests still green. Done via the
proof + a 5-agent fan-out (SystemUserRepository was the committed proof).

Follow-up cleanup (small, baselined for now): ~13 residual ripples the pass left —
offsetAccess.notFound where a shape omits an optional key (LibraryAssetResultRest
Controller:105/110, AssessmentResponseBlobFHIR:158/159, QuestionnairePortalTask:276/
320/321, ClientSearchRepository:91), a varTag.nativeType (APIProxyController:568), and
a few over-typed empty()/isset() guards now flagged "always truthy" (ClientRepository:
39/66, the *FHIRResourceService $result/$response/$item/$service guards). Each is a
1-line shape tweak (add the optional key / loosen the shape so the guard stays live).

Latent runtime bugs surfaced by the pass:
- [FIXED v0.12.1] `TagRestController` / `AssessmentReportRepository` undefined
  `$this->logger` -> use a SystemLogger instance (+ `\Exception` qualifier).
- [FIXED v0.12.1] `AssessmentGroupRestController::createAssessmentGroupsFromEntities`
  `return;` -> `continue;` (was dropping all groups on one bad blob).
- [FIXED v0.12.1] `Assignment::fromJSON` `?? 0` -> `?? ''` into string setId().
- [FIXED v0.12.1] `ClientRepository` setAssessmentId(null) -> explicit throw when a
  uid has no published assessment.
- [FIXED v0.12.1] `AssignmentTaskFHIRResourceService::update()` guard (`||`->correct
  routing) and the never-defined `updateAssignmentItem()` -> fails cleanly instead of
  a fatal; the item-update FEATURE itself is still unimplemented (see below).
- [FIXED v0.12.0] APIProxyController routeMappings, ResourceImporterService $report,
  AssessmentAppointmentController $appt, AssignedQuestionnaire ?? 0, QRespFHIR getAll.
- [DEFERRED — frontend doesn't use it] FHIR Task item-update feature (updateAssignmentItem):
  investigated the compiled SPA (public/frontend) — its `TaskService` builds a
  `status:"completed"` Task but the server call is COMMENTED OUT
  (`// return client.update<Task>(task)`); there is NO active `PUT /Task/:id`. Item
  completion goes through `client.create` of a QuestionnaireResponse ->
  QuestionnaireAssignmentListener (server), which already completes the item. So the
  Task-update path is dead by design. Leave the v0.12.1 graceful "not yet supported"
  stub; implement the backend only when the frontend re-enables the Task update (then
  the real payload is known). Other open author-decision items:
  `QuestionnaireResponseFormFHIRResourceService` half-built encounter/source linkage
  (dead stores into an undefined `$parsedResource` + a `!empty(...) == 'Practitioner'`
  precedence bug); `saveLibraryAssetResultBlob()` silently drops two caller args
  (userId, patientUUID) — audit/ownership data lost; `Client::fromJSON()` dead/broken
  (unused); APIProxyController proxy methods are dead code (only API_MAPPINGS is used).

## Phase 2 (original notes)
Target the "mixed-at-boundary" cluster — **771 errors (~54% of 1418)**, all one root:
`mixed` from DB rows / `json_decode` / request payloads flowing into typed slots.
Identifiers: argument.type 353, offsetAccess.nonOffsetAccessible 212, return.type 49,
binaryOp.invalid 41, method.nonObject 40, offsetAccess.invalidOffset 30,
foreach.nonIterable 22, nullCoalesce.* 24.

Approach — type at the SOURCE (NOT casts; casting `mixed` just relabels to cast.* here,
see [[phpstan-baseline-regen]]):
- DB rows: `/** @var array<string, ?string> $record */` at the `QueryUtils::fetch*` site
  (OpenEMR PDO returns strings/null). Use `array<string,string>` only for provably
  NOT-NULL columns; ids read as `(int) $row['id']` (string→int cast is allowed).
- `json_decode(...)` locals: `/** @var array<string,mixed> $data */` (or a shaped array).
- request payloads (`getBodyAsJson()`, query vars): shape at the entry point.
- Verify each file both ways: it must CLEAR the target errors AND not introduce new
  cast.*/return.type/childType ripple (argument.type pass showed ripple is real — budget
  a correction round). Run the phpunit suite after each batch; the P1/P2 tests cover the
  model+hydration layer this pass rewrites.

Top files (cluster-error counts): ResourceImporterService 55, AssignmentRepository 50,
QuestionnairePortalTaskFHIRResourceService 46, AssessmentGroupRestController 44,
QuestionnaireResponseFormFHIRResourceService 42, ClientRestController 33,
AssessmentAppointmentController 31, AssessmentResponseBlobFHIRResourceService 28, ...

Also fold in the deferred type-smell findings above (string setters fed `?? 0`,
AssessmentGroup::getId int-vs-int|string, AssignedAssessment assessmentId, parent::fromJSON
type reset, Client::fromJSON rewrite/removal) since this pass touches those exact lines.

## SPA route audit — DONE (2026-10-08)
Method: `public/frontend/*.js.map` embed full original TypeScript (`sourcesContent`),
479 files — extracted them and enumerated every request the SPA issues. Two transports:
(1) SMART FHIR client (`fhirService.getFhirClient().request/create`) for
Questionnaire/QuestionnaireResponse/Task/Patient; (2) `HTTPService` (`generateUrl` →
`api_url + "api/"|"portal/" + path`) for the custom `/api/v1/...` REST routes. Call-site
inventory saved to session scratchpad (`allcalls.txt`). Provider-side JS
(`providerPortal.js`, `questionnaire-audit.js`) calls NONE of the API_MAPPINGS routes
(only opens `questionnaire-audit.php` dialogs + SMART launches) — so these 39 routes have
exactly one consumer: the SPA. No internal backend cross-callers of the candidate actions;
no tests reference them (both grep-checked).

**7 of 39 routes are never called by the shipped SPA** (dead-code removal candidates):
| Route | Verb/Path | Why unused | Risk |
|---|---|---|---|
| `task.update` | PUT `/Task/:id` | `client.update<Task>` commented out; feature unimplemented (graceful stub since v0.12.1) | none (confirmed dead) |
| `library-assets.list` | GET `/library-assets` | SPA reads assets via FHIR `Questionnaire?questionnaire-code=` | low |
| `library-assets.one` | GET `/library-assets/:id` | same — FHIR `Questionnaire?_id=` | low |
| `library-asset-results.create` | POST `/library-asset-results` | `saveAssetResult()` POSTs a FHIR QuestionnaireResponse instead | low |
| `assessment-results.create` | POST `/assessment-results` | SPA only GETs `assessment-results`; results created server-side via the QR listener | low |
| `clients.one` | GET `/clients/:id` | `getClient()` reads FHIR `Patient/:id`; old `_dac$http.get("clients/"+id)` commented out | low |
| `task.one` | GET `/Task/:id` | SPA only gets Tasks via search (`Task?patient=`); never by id | medium — standard FHIR read; external SMART clients could use it |

Per-controller impact (classes stay; only the unused action + its API_MAPPINGS entry go):
- `TaskRestController` — drop `one` (medium) + `update` (dead); keeps `list`. Removing the
  `update` route also makes `TaskFHIRResourceService::update`→`AssignmentTaskFHIRResourceService::update`
  delegation unreachable (already stubbed).
- `LibraryAssetRestController` — drop `list` + `one`; keeps `create`.
- `LibraryAssetResultRestController` — drop `create`; keeps `one`.
- `AssessmentResultRestController` — drop `create`; keeps `list`.
- `ClientRestController` — drop `one`; keeps `list` + assignment/message actions.

Also still-dead (from prior sessions, independent of the route audit): APIProxyController
proxy fallback (`sendRequestAndReturnResponse`/`getUriForApiRequest`/`addAuthorizationToRequest`
+ the dead `baseUri` localhost:8000) — every live route has a callable so the Guzzle fallback
never fires; `Client::fromJSON()` (no callers). NOTE: many SPA calls hit paths with NO
OpenEMR route at all (`/admin/billing`, `/admin/companies`, `/orders`, `/items`, `/login`,
`sso`, `utils/generateClientId`, `tokens/purchase`, POST `clients/`, POST `assessment-users/`,
`users/check-username`, POST assignment update) — these are SaaS-only features of the shared
Angular codebase, inactive in the OpenEMR deployment; they are NOT backend dead code (there's
nothing to remove), just unreachable frontend paths.

## Next / roadmap (planned)
- **Execute the dead-route removals** above. Suggested split: (A) low-risk batch — the 6
  custom non-FHIR routes + `task.update` (confirmed dead); (B) hold `task.one` pending a
  decision on preserving FHIR read semantics for external SMART clients. New `ai/` branch,
  version bump (minor — behavior change to the API surface), CHANGELOG entry, run `composer
  test` + phpstan after. The 177-test suite + hydration coverage is the safety net.

## v0.12.3 bug-fix pass — DONE
Fixed the real latent bugs the characterization suite + SPA audit surfaced (honest
fixes, each with a regression test; suite 177 -> 186, phpstan clean):
- QuestionnaireResponseFormFHIRResourceService::parseFhirResource — two `== 'Patient'`/
  `== 'Practitioner'` precedence bugs + the Encounter dead-store/wrong-uuid bug.
- Assignment::fromJSON type reset; AssignedQuestionnaire::fromJSON dropped fields +
  dateCompleted preservation; AssignmentRepository setResultId ordering;
  AssessmentResultRepository non-array/missing-key handling; LibraryAssetBlobResultDTO
  string creationDate; ClientSearchQueryDTO property defaults.

Deliberately NOT fixed (documented, not one-line bugs):
- `saveLibraryAssetResultBlob` silently drops the caller's userId/patientUUID (5 args to a
  3-arg method). No DB column exists to store them (would need a `dac_LibraryAssetResultBlob`
  migration), and the only caller passing them is the DEPRECATED `library-asset-results.create`
  route (the live FHIR path passes 3). Incomplete audit feature on dead code — needs a
  migration + wiring, out of scope for a bug-fix pass.
- `LibraryAssetBlobResultDTO::jsonSerialize` omits assetId/assignmentItemId/clientId
  (asymmetric with fromDTO). Adding them changes the REST response shape and the asymmetry
  is not exercised as an actual round trip, so left as-is.
- `AssessmentGroup::getId(): int` vs `int|string` property — pure return-type smell, no
  runtime failure in practice (ids are ints); risky to widen, left as-is.

## Coverage build-out (Scope B) — BATCH 4 DONE (provider audit + repo branches)
Batch 4 (453 -> 478 tests, coverage ~45% -> ~47% lines, phpstan clean):
- QuestionnaireAuditController: dispatch routing, view guards (missing recordId / no match ->
  404), dispatch error path (-> 500). Chart/view happy paths (DB writes + real Twig) deferred.
- ClientRepository happy paths: addGroupAssignmentToClient, addAssignmentToClient, remove.
- AssignmentRepository remaining branches: audit-id item lookup, encounter reads, appointment
  guard/no-op paths, AssignedAssessmentGroup save with child items.

Bug fixed in batch 4 (the new AssignmentRepository branch test exposed it):
- [FIXED v0.12.6] AssignmentRepository::getAssignmentsForAppointmentId passed the int $pc_eid
  into TokenSearchField (requires a string) -> "Token value must be a valid string" on every
  non-zero id, so the provider appointment-render path (AssessmentAppointmentController) crashed
  for any real appointment. Cast to string.

Documented, not fixed:
- ClientRepository::addGroupAssignmentToClient feeds ?string blob name/uid into non-null
  setName/setUid; a group blob with a NULL name/uid would TypeError (happy path never hits it).
- hasCompletedAssignments/hasCompletedAssignmentItems are vacuously true with zero rows (name
  reads as if it should require a completed row) — existing intended behavior.

Scope B essentially complete. Remaining (would be batch 5, deep integration, likely low ROI):
FHIR services' insert()/update() completed DB paths (QuestionnaireResponse create flow;
Task update() completed path is on the DEPRECATED task.update route), createClientAssignment
ForProfile / AssignedQuestionnaire save (document-template + core questionnaire fixtures), and
appointment-linked read happy paths (calendar-event fixtures).

## Coverage build-out (Scope B) — BATCH 3 DONE (FHIR layer + Bootstrap)
Batch 3 (409 -> 453 tests, coverage ~38% -> ~45% lines, phpstan clean):
- FHIR delegation services (Questionnaire/QuestionnaireResponse/Task): loadSearchParameters,
  code-based getAll delegation + search-all fan-out, QuestionnaireResponse event short-circuit,
  createProvenance type guards, Task parseOpenEMRRecord.
- Leaf FhirServices (Assessment, QuestionnaireForm, AssessmentResponseBlob,
  LibraryAssetResultBlob): supportsCode + parseOpenEMRRecord/parseFhirResource mapping (mocked
  repos, no DB). QuestionnaireResponseFormFHIRResourceService already covered in the bug-fix pass.
- Task sub-services (AssignmentTask, QuestionnairePortalTask): supportsCode, FHIRTask build,
  update() guard/clean-fail branches. Completed-path DB writes deferred to integration.
- Bootstrap: container compiles, wires the public controllers, subscribeToEvents() registers
  listeners (constructed directly to avoid the singleton/Twig-runtime; Twig-dependent services
  asserted via container has() rather than get()).

Bug fixed in batch 3 (with regression test):
- [FIXED v0.12.5] QuestionnaireFHIRResourceService / TaskFHIRResourceService caught
  SearchFieldException without importing it -> the catch named a nonexistent module-namespaced
  class and the real OpenEMR\Services\Search\SearchFieldException escaped uncaught (unsupported
  questionnaire-code -> 500 instead of validation messages). Added the import.

Documented, not fixed (future hardening):
- Task services' update() completed path reads getOutput()[0] with no existence check.
- parseOpenEMRRecord methods (Task services, AssessmentFHIRResourceService isPublic) read
  required record keys by direct offset without isset guards (undefined-key warnings on
  malformed input).
- getServiceForCode() never returns null, so the `else searchAllServices` branch in
  QuestionnaireFHIRResourceService::getAll is unreachable; createProvenanceResource methods have
  harmless dead code after an if/else that both return.

Still not done (future batch 4, if wanted): QuestionnaireAuditController (provider audit/chart
flow), FHIR services' insert()/update() completed DB paths, ClientRepository happy paths and
AssignmentRepository profile/appointment branches (deep group/profile/document fixtures).

## Coverage build-out (Scope B) — BATCH 2 DONE
Goal (per decision): pure no-DB classes + raise partial repos + DB-backed repos
(Client/ClientSearch/Tag/Token/MessageTemplate) + REST controller live actions. Excludes
FHIR resource services and Bootstrap/DI.

Batch 2 DONE (349 -> 409 tests, coverage ~27% -> ~38% lines, phpstan clean):
- REST controllers: Empty, Announcement, Token, Tag (DB), SystemUser (DB), Assessment (DB),
  AssessmentGroup (DB), AssessmentReport (DB), MessageTemplate (mocked deps),
  LibraryAssetResult (DB + patient branch). ServerRestRequest is final -> tests wrap a mocked
  HttpRestRequest in a real ServerRestRequest.
- DB repo CRUD/lifecycle: AssignmentRepository (save/read/complete/remove),
  AssessmentReportRepository (getOne/getAll/create/update + null), AssessmentResultRepository
  (createResult + read), ClientSearchRepository (name/email/uuid search),
  LibraryAssetResultBlobRepository (getDecryptedAssetResultBlob join path).

Latent bugs fixed in batch 2 (all on LIVE routes, each with a regression test):
- [FIXED v0.12.4] ServerRestRequest::getUri() — returned a raw string under a UriInterface
  signature -> TypeError on every call; broke SystemUserRestController::list (assessment-users
  route). Now wraps the string in a PSR-7 Uri.
- [FIXED v0.12.4] LibraryAssetResultBlobRepository::getDecryptedAssetResultBlob — the LEFT
  JOIN referenced pd.pid but the derived table aliased it patient_pid, so EVERY call errored
  (library-asset-results.one always 500'd); and `$results[0]` on empty warned->500 for a
  missing id. Fixed join column + return null (controller -> 404).
- [FIXED v0.12.4] AssessmentReportRestController::one() — returned 200/null for an unknown id
  (dead getNotFoundResponse); now 404.
- [documented, not fixed] AssessmentReportRestController::list()/one() call getAll()/pass a
  $hostSiteId that AssessmentReportRepository::getAll($showAllReports) does not accept -> the
  arg is silently ignored (no host-site filtering). Not a crash; host sites are a SaaS concept
  unused in the OpenEMR deployment.

Scope B NOT done (explicitly out of scope / future): FHIR resource-service layer
(Questionnaire/QuestionnaireResponse/Task + FhirServices/*), Bootstrap/DI wiring,
QuestionnaireAuditController, ClientRepository happy paths (deep group/profile DB fixtures),
AssignmentRepository profile/appointment branches.

Batch 1 DONE (177 -> 349 tests, coverage ~18% -> ~27% lines, phpstan clean):
- Pure no-DB: 4 validators, Utils (RestUtils, FhirObjectDenormalizer), PaginatedResultsService,
  HTTPResponseUtils, AssignmentSerializer, Models/Role, Models/Capability,
  Models/ServerRestRequest, GlobalConfig.
- Repos: TagRepository (DB-backed), TokenRepository (stub), MessageTemplateRepository (mocked
  Twig/GlobalConfig), ClientRepository (guard branches; happy paths need deeper DB fixtures).

More latent bugs surfaced during batch 1:
- [FIXED v0.12.3] RestUtils::getResponseForProcessingResult — internal-errors branch left
  $status undefined (response builder crashed); now 500.
- [FIXED v0.12.3] TagRepository::getTagsForAssetIds — all-invalid ids built an invalid
  "IN ()"; now returns [] early.
- [documented, not fixed] ServerRestRequest::getUri(): UriInterface delegates to
  HttpRestRequest::getUri(): string -> TypeErrors whenever called. Return-type fix is a
  signature change; left for a focused follow-up.
- [documented] AssignmentSerializer::deserialize throws "Invalid assignment type" for
  TemplateProfile even though it is a valid group type in Assignment::ASSIGNMENT_TYPES
  (asymmetry); and leaf types return a PLAIN Assignment at top level with the concrete
  subclass only as items[0] (discarded when no items). Characterized as-is.
- [documented] Models/Capability is an orphaned abstract class in the GLOBAL namespace,
  unreferenced and not PSR-4 autoloadable (dead code).
- [documented] AssessmentGroupValidator "db-update" context defines no rules (validates
  anything as valid).


## PHPStan × coverage map (line-level, 2026-10-08) — drives the pre-fix test work
Method: ran phpstan with the module baseline OFF (846 current errors, with line numbers),
mapped each error to its enclosing method, and checked that method's coverage via clover.
Regenerate with: `<scratchpad>/precision.py <clover.xml> <phpstan-errors.json> <DEST>/src`.

Where the 846 flagged errors live:
- **355 (42%) in methods EXERCISED by tests** -> safe to do aggressive phpstan fixes now.
- **450 (53%) in methods NOT exercised** -> write a test BEFORE fixing (list below).
- 34 (4%) in @deprecated methods -> leave in the baseline, do not test/fix.
- 7 class-level (use/property/docblock) -> n/a.

### "Need a test" targets (method : #errors), grouped by tranche
TRANCHE A — high-value + unit-testable (do first, ~130 errors):
- RestControllers/ClientRestController: list 7, addAssignmentGroupToClient 9, sendMessageToClient 8,
  removeAssignmentFromClient 6, addAssignmentToClient 5  (0% covered today)
- RestControllers/AssessmentReportRestController: create 12, update 11
- RestControllers/AssessmentGroupRestController: create 6, addAssessmentToGroup 6, updateAssessmentVersionForGroup 6
- RestControllers/AssessmentRestController: createAssessmentForContext 10, update 1
- RestControllers/QuestionnaireResponseRestController: create 8, getAll 5, getOne 1, __construct 1
- Services/FhirServices/QuestionnaireResponseFormFHIRResourceService: parseOpenEMRRecord 22
  (read direction; parseFhirResource already covered) — easy win, mostly pure.

TRANCHE B — integration-heavy (real fixtures; cover where cheap, else leave baselined):
- FHIR insert/insertOpenEmrRecord (QuestionnaireResponse-create DB flow):
  AssessmentResponseBlobFHIRResourceService::insertOpenEmrRecord 17,
  LibraryAssetResultBlobFHIRResourceService::insertOpenEmrRecord 10,
  QuestionnaireResponseFormFHIRResourceService::insertOpenEMRRecord 4,
  LibraryAssetResultBlobRepository::saveLibraryAssetResultBlob 10.
- Controllers/AssessmentAppointmentController (51): appointment wizard / notification / digital
  documents screens — needs appointment + document-template fixtures.
- RestControllers/QuestionnaireAuditController: actionChartAssignmentToEncounter 13 + render 11.
- Listeners/QuestionnaireResponseRestListener 11, Listeners/QuestionnaireAssignmentListener 10
  (QR-save -> assignment-completion event glue).
- Services/ClientMessageDispatcher 8 (notifications; needs mailer mocking).
- Services/Task/QuestionnairePortalTaskFHIRResourceService: searchForOpenEMRRecords 6,
  getTaskDataForTemplates 6.

LEAVE BASELINED (dead/deprecated):
- The 34 @deprecated-method errors.
- APIProxyController proxy FALLBACK only: sendRequestAndReturnResponse / getUriForApiRequest /
  addAuthorizationToRequest (~5; SPA audit confirmed the Guzzle fallback never fires). NOTE the
  rest of APIProxyController (proxyGet/Post/getCallableForApiRequest, ~15) is live-but-untested
  and belongs in a test tranche, not here.

Plan: build Tranche A (and cheap parts of B) FIRST, THEN start the aggressive phpstan fixes on
the now-netted methods; keep deprecated/dead errors baselined.

## Progress log
- 2026-10-08: Scope B batch 4 — QuestionnaireAuditController guards + ClientRepository happy
  paths + AssignmentRepository remaining branches (453 -> 478 tests, coverage ~45% -> ~47%
  lines). Fixed the getAssignmentsForAppointmentId int-into-TokenSearchField crash on the
  provider appointment path. phpstan clean. v0.12.6 on branch ai/coverage-batch-4.
- 2026-10-08: Scope B batch 3 — FHIR resource-service layer (delegation + leaf + Task
  sub-services) + Bootstrap container/DI smoke tests (409 -> 453 tests, coverage ~38% -> ~45%
  lines). Fixed the uncaught SearchFieldException (missing import) in the two delegation
  services. phpstan clean. v0.12.5 on branch ai/coverage-batch-3.
- 2026-10-08: Scope B batch 2 — REST controller + DB-repo CRUD tests (349 -> 409 tests,
  coverage ~27% -> ~38% lines). Fixed 3 live-route bugs found while testing (getUri TypeError,
  the library-asset-results SQL join + null, assessment-reports one() 404). phpstan clean.
  v0.12.4 on branch ai/coverage-batch-2.

- 2026-10-08: Plan created. phpunit.xml given a `<source>`/testsuite so coverage can
  target `src/`. First pure-unit test (`Models/AssignmentTest`) added as the pattern.
- 2026-10-08: PR #5 (the 8.4.1 port) merged to main; stale PRs #3/#4 closed. This
  work continues on branch `ai/test-coverage-and-source-typing` off updated main.
  PCOV installed; coverage baseline generated: 9.3% (see above).
- 2026-10-08: P1 pure-unit hydration batch complete — 15 new test files (Models,
  Assigned* subclasses, DTOs, error/code models). Suite 15 → 136 tests / 428
  assertions, 2 incomplete. Coverage 9.3% → **15.0%**; the P1 classes are now ~100%.
  Latent bugs above were surfaced in the process.
- 2026-10-08: Source-typing pass (Phase 2) complete — proof (SystemUserRepository) +
  5-agent fan-out over the mixed-at-boundary cluster. Baseline 1418 -> 882; zero cast.*;
  176 tests green. v0.12.0. Several real bugs fixed, more logged above for follow-up.
- 2026-10-08: v0.12.3 bug-fix pass — fixed the real latent bugs from the audit (FHIR
  parse precedence/encounter, model fromJSON round-trips, repo hydration ordering, DTO
  date parsing), each with a regression test. Suite 177 -> 186, phpstan clean. Then
  started the Scope B coverage build-out.
- 2026-10-08: SPA route audit complete — extracted the full original TS from the frontend
  source maps (479 files), inventoried every request, cross-referenced all 39 API_MAPPINGS
  routes. 7 routes unused by the shipped SPA (6 low-risk + `task.one` medium). Recorded the
  matrix + per-controller impact above. No removals made yet (awaiting scope decision).
- 2026-10-08: P2 repository-hydration batch complete — 5 new test files (AssignmentRepository
  + AssessmentRepository/AssessmentResultRepository/LibraryAssetResultBlobRepository/
  SystemUserRepository), reflection into the private hydrators on the pure no-DB path.
  Suite 136 → 173 tests / 583 assertions. Coverage 15.0% → **17.7%** (repo CRUD bulk is
  P3). `Client::fromJSON` confirmed dead/unused. Next: P3 DB-backed integration.
