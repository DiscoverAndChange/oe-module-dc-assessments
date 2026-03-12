# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is an OpenEMR custom module (`oe-module-dc-assessments`) that adds patient assessment/questionnaire/assignment capabilities to OpenEMR. It provides FHIR endpoints (Questionnaire, QuestionnaireResponse, Task), a provider-facing assignment management UI, and a patient-facing Angular SPA for completing assignments.

**Namespace:** `OpenEMR\Modules\DiscoverAndChange\Assessments\` (PSR-4 mapped to `src/`)
**Requires:** OpenEMR >= 7.0.0, PHP 8.2+

## Commands

```bash
# Install dependencies
composer install

# Static analysis (runs phpstan from OpenEMR root against module src/)
composer phpstan

# Code modernization (dry-run / apply, runs rector from OpenEMR root)
composer rector-check
composer rector-fix

# Run unit tests (no database required, uses tests/bootstrap-unit.php)
composer test

# Run integration tests (requires OpenEMR Docker with database, uses tests/bootstrap.php)
composer test-integration

# Clear cached DI container (do this after changing Bootstrap service definitions)
rm -f cache/container.php cache/container.php.meta
```

All scripts automatically `cd` to the OpenEMR root directory (4 levels up) to use its tooling, passing the module path via `$MODULE_DIR`.

## Architecture

### Module Entry Points

- **`openemr.bootstrap.php`** — Loaded by OpenEMR's module loader. Calls `Bootstrap::instantiate()` with the EventDispatcher and Kernel. This is how the module hooks into the running OpenEMR application.
- **`moduleConfig.php`** — Backend config page entry point. Dispatches via `BackendDispatchController`.
- **`public/backend/index-backend.php`** — Backend API entry point for non-FHIR actions (config, appointment rendering, notifications).
- **`public/frontend/index.php`** — Angular SPA entry point for the patient-facing portal (`ignoreAuth = true`).

### Bootstrap & Dependency Injection

`Bootstrap.php` is the central orchestrator (singleton). It:
1. Builds a **Symfony DI Container** with all services (repositories, controllers, FHIR services)
2. **Caches** the compiled container to `cache/container.php` with metadata-based auto-refresh
3. Subscribes to **OpenEMR events** (GlobalsInitializedEvent, MenuEvent, ScriptFilterEvent, TemplatePageEvent, TwigEnvironmentEvent)
4. Registers **FHIR/REST API routes** via `APISetupController`

Service definitions use `new Definition()` with `new Reference()` for dependencies. After modifying service wiring, delete the cache files.

### Event-Driven Integration

The module uses the **IStaticEventSubscriber** pattern — classes implement a static `subscribeToEvents(Container, EventDispatcher)` method called during bootstrap. Key subscribers:

| Class | Events |
|-------|--------|
| `APISetupController` | RestApiCreateEvent, RestApiScopeEvent, RestApiResourceServiceEvent, RestApiSecurityCheckEvent |
| `AssessmentAppointmentController` | AppointmentRenderEvent, ServiceDeleteEvent, AppointmentDialogCloseEvent |
| `QuestionnaireAssignmentListener` | ServiceSaveEvent::EVENT_POST_SAVE |
| `QuestionnaireResponseRestListener` | fhir.questionnaire_response.pre_insert, fhir.questionnaire_response.search |

### API Route Registration

`APIProxyController::API_MAPPINGS` is the central route map — a constant array mapping 20+ endpoints to controllers, actions, HTTP methods, contexts (user/patient/system), ACLs, and scopes. `APISetupController` reads this to register routes with OpenEMR's REST framework.

Route contexts: `fhirRouteMap` (FHIR endpoints), `routeMap` (user API), `portalRouteMap` (patient API).

### FHIR Resource Services

FHIR services extend OpenEMR's `FhirServiceBase` and implement `IResourceReadableService`/`IResourceSearchableService`. The Questionnaire and Task services use a **delegation pattern** — they route to sub-services based on resource type codes (e.g., `QuestionnaireFHIRResourceService` delegates to `AssessmentFHIRResourceService`, `QuestionnaireFormFHIRResourceService`, or `LibraryAssetFHIRResourceService`).

### Data Flow: Assignment Lifecycle

1. Provider creates assignment via appointment UI → `AssessmentAppointmentController`
2. `AssignmentRepository` creates `dac_Assignment` + `dac_AssignmentItem` rows
3. Patient sees Task in portal, completes QuestionnaireResponse
4. `QuestionnaireAssignmentListener` catches `ServiceSaveEvent::EVENT_POST_SAVE`
5. `AssignmentCompleter` marks complete, creates PDF, sends notifications via `ClientMessageDispatcher`

### Database

All tables are prefixed with `dac_` (defined in `table.sql`, dropped in `cleanup.sql`). Key tables:
- `dac_Assignment` / `dac_AssignmentItem` — assignment tracking
- `dac_AssessmentBlob` / `dac_AssessmentResultBlob` — assessment JSON storage
- `dac_LibraryAssetBlob` / `dac_LibraryAssetResultBlob` — library asset storage
- `dac_AssessmentGroup` / `dac_AssessmentGroupAssessmentBlob` — grouping

The module does NOT use Doctrine ORM for queries. Repositories use OpenEMR's `QueryUtils` / `sqlQuery()` with raw SQL and complex LEFT JOIN queries for eager loading.

### Templates

Twig 3.x templates are in `templates/discoverandchange/`. The module overrides OpenEMR templates (notably OAuth2 pages) by prepending its template path via `TwigEnvironmentEvent`.

### Frontend

The patient-facing UI is a pre-compiled **Angular SPA** in `public/frontend/`. It communicates with OpenEMR via FHIR endpoints. Configuration (FHIR URLs, SMART styles) is injected server-side by `FrontendDispatchController`.

Provider-side JavaScript is in `public/backend/assets/js/` (vanilla JS, not Angular).

## Conventions

- `declare(strict_types=1)` in all files
- Namespace: `OpenEMR\Modules\DiscoverAndChange\Assessments\*` (PSR-4)
- Full type hints on all parameters and return values
- XXE-hardened XML parsing: use `LIBXML_NONET` flag, `libxml_use_internal_errors(true)` pattern
- Test fixtures live in `tests/Fixtures/`; test helpers in `tests/Support/`; mocks in `tests/Mock/`
- Always create a new branch with the `ai/` prefix before making code changes (e.g., `ai/fix-assignment-query`). Never commit directly to `main`.
- Pull requests must also use the `ai/` branch prefix.
- Every PR must bump the version number in all three locations and add a CHANGELOG entry:
  - `info.txt` — format: `Discover and Change Assessments v<major>.<minor>.<patch>`
  - `version.php` — update `$v_major`, `$v_minor`, `$v_patch` variables
  - `CHANGELOG.md` — prepend a new entry: `v<version> <summary>\n\n  <details>`
  - Use semver: bump patch for fixes, minor for features, major for breaking changes.
  - When merging `main` into a PR branch causes a version conflict, resolve by setting the PR's version to be higher than whatever is on `main`.
