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
Then generate a report (PCOV is off by default, enable per-run):
```
cd ~/projects/openemr-wt-file-manifest \
 && php -d pcov.enabled=1 vendor/bin/phpunit \
      --configuration=interface/modules/custom_modules/oe-module-dc-assessments/phpunit.xml \
      --coverage-text --coverage-html coverage-html
```
`phpunit.xml` now declares `<source>` = `src/`, so coverage is scoped to the module.
Treat the report as a **map to prioritize**, not a target — line coverage ≠ assertion
quality. Record the baseline % here once generated.

Coverage baseline: _not yet generated (install PCOV first)_.

## Strategy: order by (change-risk × path-criticality)
1. **Pure-unit hydration** (no DB) — the exact code source-typing rewrites: array→object.
2. **Repository hydration** — `hydrate*FromRecord` with crafted row arrays (no/low DB).
3. **DB-backed integration** — CRUD + importers against `oe-test-db`.
4. **FHIR/service integration** — endpoint/delegation behavior (hardest; needs harness).

## Target checklist
### P1 — pure-unit hydration (no DB)
- [x] `Models/Assignment` — fromJSON + jsonSerialize round-trip, type validation, items
- [ ] `Models/AssignedAssessment`, `AssignedQuestionnaire`, `AssignedLibraryAsset`,
      `AssignedAssessmentGroup`, `AssignedTemplateProfile` — fromJSON (incl. parent call)
- [ ] `Models/Client` — fromJSON, sortAssignmentsByDateAssigned ordering
- [ ] `Models/SystemUser`, `AssessmentSummary`, `AssessmentSnippet`, `AssessmentGroup`
- [ ] `DTO/LibraryAssetBlobDTO` — fromDTO + jsonSerialize (watch nullable ?string fields)
- [ ] `DTO/LibraryAssetBlobResultDTO` — fromDTO + generateId
- [ ] `DTO/ClientSearchQueryDTO` — populateFromRequest, isValid
- [ ] `Models/SystemError`, `ErrorCode`/`ErrorCodeStatus` — code→status/string mapping
### P2 — repository hydration (crafted row arrays)
- [ ] `AssignmentRepository::hydrateAssignedFromRecord` / `hydrateItemFromRecord`
- [ ] `AssessmentRepository::hydrateAssessmentSummaryFromDatabaseRecord`
- [ ] `SystemUserRepository::hydrateUser`
- [ ] `LibraryAssetBlobRepository` / `LibraryAssetResultBlobRepository` `hydrate*`
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
- `Models/Assignment::fromJSON` does `setId($json["id"] ?? 0)` but `setId(string)` —
  a missing `id` passes int `0` to a string param → `TypeError` under strict_types.
  (Documented by a test asserting the happy path; fix separately if desired.)

## Progress log
- 2026-10-08: Plan created. phpunit.xml given a `<source>`/testsuite so coverage can
  target `src/`. First pure-unit test (`Models/AssignmentTest`) added as the pattern.
