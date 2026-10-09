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

Bring the target OpenEMR stack up (the one serving the SPA, e.g. `https://localhost:9300`) and ensure
the module is enabled in Modules admin, then:

```bash
# from the deployed module root
composer uat:browser
```

Without `DC_BROWSER_UAT=1` (which the composer script sets), or when the stack/DB/Playwright is not
ready, every test **self-skips** with an actionable message — a bare `composer test` is unaffected.

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

- **Done:** harness + gating/preflight/teardown; patient-credential seeding; the `patient login`
  spec (login → SPA dashboard renders) wired end-to-end through PHPUnit.
- **TODO (needs a running stack to author against real DOM):** `seedAssignment()` (inject a battery +
  assign it) on the PHP side, and the `test.fixme()` steps in `assessment-workflow.spec.ts` (open
  assignment → answer/submit → provider-side review). These complete the full 13-step scenario.
