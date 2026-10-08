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

## Phase 2: the source-typing pass (next)
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

## Progress log
- 2026-10-08: Plan created. phpunit.xml given a `<source>`/testsuite so coverage can
  target `src/`. First pure-unit test (`Models/AssignmentTest`) added as the pattern.
- 2026-10-08: PR #5 (the 8.4.1 port) merged to main; stale PRs #3/#4 closed. This
  work continues on branch `ai/test-coverage-and-source-typing` off updated main.
  PCOV installed; coverage baseline generated: 9.3% (see above).
- 2026-10-08: P1 pure-unit hydration batch complete — 15 new test files (Models,
  Assigned* subclasses, DTOs, error/code models). Suite 15 → 136 tests / 428
  assertions, 2 incomplete. Coverage 9.3% → **15.0%**; the P1 classes are now ~100%.
  Latent bugs above were surfaced in the process.
- 2026-10-08: P2 repository-hydration batch complete — 5 new test files (AssignmentRepository
  + AssessmentRepository/AssessmentResultRepository/LibraryAssetResultBlobRepository/
  SystemUserRepository), reflection into the private hydrators on the pure no-DB path.
  Suite 136 → 173 tests / 583 assertions. Coverage 15.0% → **17.7%** (repo CRUD bulk is
  P3). `Client::fromJSON` confirmed dead/unused. Next: P3 DB-backed integration.
