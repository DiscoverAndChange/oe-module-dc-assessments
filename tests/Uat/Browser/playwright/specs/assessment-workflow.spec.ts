import { test, expect, Page, Browser } from '@playwright/test';

/**
 * Patient + provider SMART-app workflow specs. Credentials / names arrive via env from the PHPUnit
 * driver (BrowserUatTestCase). Titles are matched by PHPUnit's --grep, so keep them stable.
 */

const FRONTEND = '/interface/modules/custom_modules/oe-module-dc-assessments/public/frontend';
const DASHBOARD_PATH = FRONTEND + '/std/assessments/dashboard';
const ADMIN_PATH = FRONTEND + '/std/admin';

function requireEnv(name: string): string {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Missing required env ${name} (the PHPUnit driver should set it).`);
  }
  return value;
}

/**
 * Patient login via OpenEMR's OAuth2 "portal-api" flow (field names are core's: username/password,
 * submitted by the button name=user_role value=portal-api), then the SMART scope-authorize consent,
 * landing on the SPA dashboard.
 */
async function patientLogin(page: Page, username: string, password: string): Promise<void> {
  await page.goto(DASHBOARD_PATH, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('input[name="username"]', { timeout: 30_000 });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[name="user_role"][value="portal-api"]');
  const authorize = page.locator('button:has-text("Authorize")');
  if (await authorize.first().isVisible({ timeout: 10_000 }).catch(() => false)) {
    await authorize.first().click();
  }
  await expect(page.locator('app-root')).toBeVisible({ timeout: 30_000 });
}

/** Full patient path: open the assigned assessment, answer it, submit, land on the completion screen. */
async function completeAssignmentAsPatient(page: Page, username: string, password: string, name: string): Promise<void> {
  await patientLogin(page, username, password);
  await expect(page.getByText(name).first()).toBeVisible({ timeout: 20_000 });
  await page.locator('input[value="Get started"]').first().click();
  await expect(page).toHaveURL(/\/take\//, { timeout: 15_000 });
  await page.waitForSelector('input[type=radio]', { timeout: 15_000 });
  await page.locator('input[type=radio]').last().check({ force: true });
  const submitPost = page.waitForResponse(
    (r) => /\/QuestionnaireResponse/.test(r.url()) && r.request().method() === 'POST',
    { timeout: 20_000 },
  );
  await page.locator('input[value="Submit"]').first().click();
  const resp = await submitPost;
  expect(resp.url(), 'assessment results must submit to the portal base, not FHIR').toContain('/portal/');
  expect(resp.status(), 'portal QuestionnaireResponse write should succeed').toBe(201);
  await expect(page.getByText(/all of your assignments are complete/i)).toBeVisible({ timeout: 20_000 });
}

/**
 * Provider login to the admin app: the standalone admin launch uses the CONFIDENTIAL provider client
 * (the "OpenEMR Login" button, user_role=api), and the confidential token exchange is brokered
 * server-side (public/backend/provider-token.php). Lands on the provider Patients List.
 */
async function providerLogin(page: Page, username: string, password: string): Promise<void> {
  await page.goto(ADMIN_PATH, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('input[name="username"]', { timeout: 30_000 });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[name="user_role"][value="api"]');
  const authorize = page.locator('button:has-text("Authorize")');
  if (await authorize.first().isVisible({ timeout: 10_000 }).catch(() => false)) {
    await authorize.first().click();
  }
  await expect(page.getByText(/Patients List/i).first()).toBeVisible({ timeout: 30_000 });
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
    await expect(page.getByText(name).first()).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('input[value="Get started"]').first()).toBeVisible();
  });

  test('patient opens, answers and submits the assigned assessment', async ({ page }) => {
    await completeAssignmentAsPatient(
      page,
      requireEnv('DC_E2E_PATIENT_USER'),
      requireEnv('DC_E2E_PATIENT_PASS'),
      requireEnv('DC_E2E_ASSESSMENT_NAME'),
    );
  });
});

test.describe('provider review', () => {
  test('provider sees the patient\'s completed assessment in the admin app', async ({ browser }: { browser: Browser }) => {
    const name = requireEnv('DC_E2E_ASSESSMENT_NAME');
    const lastName = requireEnv('DC_E2E_PATIENT_LASTNAME');

    // 1. patient completes the assessment (own context)
    const patientCtx = await browser.newContext({ ignoreHTTPSErrors: true });
    const patientPage = await patientCtx.newPage();
    await completeAssignmentAsPatient(
      patientPage,
      requireEnv('DC_E2E_PATIENT_USER'),
      requireEnv('DC_E2E_PATIENT_PASS'),
      name,
    );
    await patientCtx.close();

    // 2. provider logs in to the admin app (confidential provider client, server-side token broker)
    const providerCtx = await browser.newContext({ ignoreHTTPSErrors: true });
    const providerPage = await providerCtx.newPage();
    await providerLogin(
      providerPage,
      process.env.DC_E2E_PROVIDER_USER || 'admin',
      process.env.DC_E2E_PROVIDER_PASS || 'pass',
    );

    // 3. open the patient's client record (the list auto-shows recent clients; the last name is unique)
    await providerPage.getByText(lastName).first().click();
    await expect(providerPage).toHaveURL(/\/std\/admin\/client\//, { timeout: 15_000 });

    // 4. the submitted assessment shows as completed with a report to view
    await expect(providerPage.getByText(name).first()).toBeVisible({ timeout: 20_000 });
    await expect(providerPage.getByRole('button', { name: /view report/i }).first()).toBeVisible();
    await providerCtx.close();
  });
});
