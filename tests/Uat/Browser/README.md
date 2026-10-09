# Browser (Playwright) UAT tier

End-to-end browser tests for the patient SMART-app workflow. **PHPUnit is the entry point and the
reporter**; it seeds the database, runs [Playwright](https://playwright.dev/) for the actual browser
interaction, and parses Playwright's JSON report back into PHPUnit assertions. Playwright is just the
browser engine — you never invoke it directly in CI.

This mirrors `oe-module-ihi`'s `tests/Uat/Browser` conventions (opt-in flag, ordered preflight with
`markTestSkipped`, bounded self-cleaning teardown, a dedicated bootstrap, `#[Group('browser')]`,
invoked by a `composer uat:browser` script), but swaps Panther/Selenium for Playwright driven as a
subprocess.

## Layout

```
tests/Uat/Browser/
  bootstrap.php                     # dedicated bootstrap: core autoloader + module namespaces
  BrowserUatTestCase.php            # gating, preflight, PDO seeding/teardown, Playwright bridge
  AssessmentWorkflowBrowserTest.php # the #[Group('browser')] UAT test(s)
  playwright/
    package.json                    # isolated Node project (@playwright/test)
    playwright.config.ts            # JSON reporter -> DC_E2E_REPORT; baseURL from DC_UAT_BASE_URL
    specs/assessment-workflow.spec.ts
```

## One-time setup

The Playwright project is isolated (its own `node_modules`, not the module's `vendor/`). From the
**deployed** module location (inside the OpenEMR tree):

```bash
cd interface/modules/custom_modules/oe-module-dc-assessments/tests/Uat/Browser/playwright
npm ci            # or: npm install
npx playwright install chromium
```

## Running

### 1. Bring up / provision the stack (one-time per fresh stack)

Use the OpenEMR **8.4** dev stack (the module requires >= 8.4). From a worktree checkout:

```bash
cd <openemr-8.4-worktree>/docker/development-easy && docker compose up -d mysql openemr
# find the published ports (the worktree picks non-default ones to avoid collisions):
docker compose port openemr 443   # -> e.g. 0.0.0.0:9302   (SPA base URL)
docker compose port mysql  3306   # -> e.g. 0.0.0.0:8322   (UAT PDO port)
```

The `openemr/openemr:flex` first boot can leave `vendor/` incomplete (login 500s with a missing
`Laminas\Db\...` class). If so: `docker exec <openemr-container> sh -c 'cd /var/www/localhost/htdocs/openemr && composer install --no-interaction --no-scripts'`.

Provision the module + the stack prerequisites the SMART flow needs (enables the module, runs
`table.sql`, turns on the REST/FHIR/portal APIs + oauth, disables `enforce_signin_email`, registers &
enables the SMART client, and fixes the client `redirect_uri` / `site_addr_oath` to the public base
URL) — run as the web user, passing the public base URL:

```bash
docker exec <openemr-container> sh -c \
  "cd /var/www/localhost/htdocs/openemr && su -s /bin/sh apache -c \
   'php interface/modules/custom_modules/oe-module-dc-assessments/tests/Uat/Browser/tools/provision-stack.php baseurl=https://localhost:9302'"
```

### 2. Run the UAT

```bash
# from the deployed module root, pointing at the stack's published ports
DC_UAT_BASE_URL=https://localhost:9302 \
DC_DB_HOST=127.0.0.1 DC_DB_PORT=8322 DC_DB_USER=root DC_DB_PASS=root DC_DB_NAME=openemr \
composer uat:browser
```

Without `DC_BROWSER_UAT=1` (which the composer script sets), or when the stack/DB/Playwright is not
ready, every test **self-skips** with an actionable message — a bare `composer test` is unaffected.

> Node note: `@playwright/test` is pinned to **1.48.2** (last line supporting Node 18). On Node 20+
> you can bump it.

### Preflight order (each a specific skip reason)

1. `DC_BROWSER_UAT=1` set?
2. Playwright installed (`playwright/node_modules/.bin/playwright`)?
3. App base URL reachable (TCP)?
4. DB reachable and `modules.mod_active = 1` for `oe-module-dc-assessments`?

## Configuration (env vars, with defaults)

| Var | Default | Purpose |
|-----|---------|---------|
| `DC_BROWSER_UAT` | _(unset)_ | `1` opts the tier in |
| `DC_UAT_BASE_URL` | `https://localhost:9300` | base URL of the running stack |
| `DC_DB_HOST` / `DC_DB_PORT` / `DC_DB_NAME` / `DC_DB_USER` / `DC_DB_PASS` | `localhost` / `3306` / `openemr` / `openemr` / `openemr` | PDO for seeding + teardown + preflight |
| `DC_PLAYWRIGHT_DIR` | `tests/Uat/Browser/playwright` | override the Playwright project location (e.g. a persistent install) |

The driver sets `DC_UAT_BASE_URL`, `DC_E2E_REPORT`, and `DC_E2E_PATIENT_USER` / `DC_E2E_PATIENT_PASS`
(the seeded credentials) for Playwright automatically.

## Seeding & teardown

`BrowserUatTestCase` talks to the target DB over a raw PDO (it does **not** initialise OpenEMR's DB
globals). It captures a baseline `MAX(patient_data.id)` once per run and, in `tearDownAfterClass()`,
deletes only rows created this run (`id > baseline`) — `dac_AssignmentItem` → `dac_Assignment` →
`patient_access_onsite` → `patient_data` — swallowing errors so cleanup never masks a test failure.

`seedPatientWithPortalCredentials()` creates a patient with **active** portal credentials
(`portal_pwd_status = 1`, no onetime — the verified state the `portal_force_credential_reset='1'` fix
produces), so the first SMART login works.

## Status / TODO

- **Done & verified against a live OpenEMR 8.4 stack:** harness + gating/preflight/teardown;
  patient-credential seeding; the `provision-stack.php` provisioner; the `patient login` spec
  (SMART/OAuth2 login → SPA dashboard renders) passing end-to-end through PHPUnit
  (seed → Playwright → assert → teardown).
- **Done & verified (assignment workflow):** `tools/seed-assignment.php` seeds a patient + an
  assessment (with a real question) + an assignment via the module's own services;
  `BrowserUatTestCase::seedAssignedAssessment()` invokes it over `docker exec`. The SPA specs verify
  the dashboard lists the assigned assessment and the patient can open it, answer, and submit.
- **KNOWN BLOCKER (documented in the spec):** patient Submit posts `QuestionnaireResponse` to the
  **FHIR** base and core denies patient FHIR writes (`AuthorizationListener` → 401). The module also
  exposes the patient-write route on the **portal** base, which core allows — so the fix is to submit
  there. The submit spec currently asserts the 401 so the test captures the real failure.
- **TODO:** provider-side review step (log in as provider → assessment-management app → open result);
  flip the submit assertion to success once the SPA targets the portal base.
