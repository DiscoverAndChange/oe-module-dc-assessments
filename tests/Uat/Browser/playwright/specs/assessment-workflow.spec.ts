import { test, expect, Page } from '@playwright/test';

/**
 * Patient SMART-app workflow specs. Credentials + the assigned assessment's name arrive via env from
 * the PHPUnit driver (BrowserUatTestCase). Titles are matched by PHPUnit's --grep, so keep them stable.
 */

const DASHBOARD_PATH =
  '/interface/modules/custom_modules/oe-module-dc-assessments/public/frontend/std/assessments/dashboard';

function requireEnv(name: string): string {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Missing required env ${name} (the PHPUnit driver should set it).`);
  }
  return value;
}

/**
 * Drive OpenEMR's OAuth2 "portal-api" login (field names are core's: username / password, submitted
 * by the button name=user_role value=portal-api), then the SMART scope-authorize consent screen
 * (an "Authorize" button, shown first time per patient+client), landing on the SPA dashboard.
 */
async function patientLogin(page: Page, username: string, password: string): Promise<void> {
  await page.goto(DASHBOARD_PATH, { waitUntil: 'domcontentloaded' });

  await page.waitForSelector('input[name="username"]', { timeout: 30_000 });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[name="user_role"][value="portal-api"]');

  // SMART scope-authorize consent (first authorization for this patient+client)
  const authorize = page.locator('button:has-text("Authorize")');
  if (await authorize.first().isVisible({ timeout: 10_000 }).catch(() => false)) {
    await authorize.first().click();
  }

  // the Angular SPA has booted when <app-root> renders
  await expect(page.locator('app-root')).toBeVisible({ timeout: 30_000 });
}

test.describe('patient login', () => {
  test('patient login reaches the assessments dashboard', async ({ page }) => {
    await patientLogin(page, requireEnv('DC_E2E_PATIENT_USER'), requireEnv('DC_E2E_PATIENT_PASS'));
    await expect(page.locator('body[data-client-id]')).toHaveCount(1);
    await expect(page.locator('input[name="password"]')).toHaveCount(0);
  });
});

test.describe('assessment workflow', () => {
  test('patient sees the assigned assessment on the dashboard', async ({ page }) => {
    const name = requireEnv('DC_E2E_ASSESSMENT_NAME');
    await patientLogin(page, requireEnv('DC_E2E_PATIENT_USER'), requireEnv('DC_E2E_PATIENT_PASS'));

    // the dashboard lists the clinician-assigned assessment + a "Get started" control
    await expect(page.getByText(name).first()).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('input[value="Get started"]').first()).toBeVisible();
  });

  test('patient opens, answers and submits the assigned assessment', async ({ page }) => {
    const name = requireEnv('DC_E2E_ASSESSMENT_NAME');
    await patientLogin(page, requireEnv('DC_E2E_PATIENT_USER'), requireEnv('DC_E2E_PATIENT_PASS'));
    await expect(page.getByText(name).first()).toBeVisible({ timeout: 20_000 });

    // open the assessment (navigates to .../take/<assignmentItemId>)
    await page.locator('input[value="Get started"]').first().click();
    await expect(page).toHaveURL(/\/take\//, { timeout: 15_000 });

    // the question renders as radio options; answer it, then submit
    await page.waitForSelector('input[type=radio]', { timeout: 15_000 });
    await page.locator('input[type=radio]').last().check({ force: true });

    const submitPost = page.waitForResponse(
      (r) => /\/QuestionnaireResponse/.test(r.url()) && r.request().method() === 'POST',
      { timeout: 20_000 },
    );
    await page.locator('input[value="Submit"]').first().click();
    const resp = await submitPost;

    // The SPA submits assessment results to the module's PORTAL route
    // (/apis/default/portal/QuestionnaireResponse), NOT the FHIR base: OpenEMR core categorically
    // denies patient-role writes to FHIR resources (AuthorizationListener -> 401), even though the
    // token carries patient/QuestionnaireResponse.write. The portal route (which core allows) is wired
    // via the SPA's HTTPService.generateUrl() patient branch + the module's getBody() stream fix.
    expect(resp.url(), 'assessment results must submit to the portal base, not FHIR').toContain('/portal/');
    expect(resp.status(), 'portal QuestionnaireResponse write should succeed').toBe(201);

    // back on the dashboard with the completion confirmation
    await expect(page.getByText(/all of your assignments are complete/i)).toBeVisible({ timeout: 20_000 });
  });
});
