import { test, expect, Page } from '@playwright/test';

/**
 * Patient SMART-app workflow specs. Credentials + seeded ids arrive via env from the PHPUnit driver
 * (BrowserUatTestCase). Titles are matched by PHPUnit's --grep, so keep them stable.
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
 * Drive OpenEMR's OAuth2 "portal-api" login (the module overrides the template, but the field names
 * are core's: username / password, submitted by the button name=user_role value=portal-api). Handles
 * the optional scope-authorize / patient-select interstitials if the stack is configured to show them.
 */
async function patientLogin(page: Page, username: string, password: string): Promise<void> {
  // Entering the SPA triggers the SMART launch, which bounces to the OAuth2 authorize/login screen.
  await page.goto(DASHBOARD_PATH, { waitUntil: 'domcontentloaded' });

  await page.waitForSelector('form#userLogin, input[name="username"]', { timeout: 30_000 });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[name="user_role"][value="portal-api"]');

  // Optional consent/patient-select steps — only present on some configs. Best-effort, short wait.
  const authorizeButton = page.locator('button:has-text("Authorize"), input[value="Authorize"]');
  if (await authorizeButton.first().isVisible({ timeout: 3_000 }).catch(() => false)) {
    await authorizeButton.first().click();
  }
}

test.describe('patient login', () => {
  test('patient login reaches the assessments dashboard', async ({ page }) => {
    const username = requireEnv('DC_E2E_PATIENT_USER');
    const password = requireEnv('DC_E2E_PATIENT_PASS');

    await patientLogin(page, username, password);

    // The Angular SPA has booted when <app-root> renders and the body carries the injected config.
    await expect(page.locator('app-root')).toBeVisible({ timeout: 30_000 });
    await expect(page.locator('body[data-client-id]')).toHaveCount(1);

    // Should NOT be sitting on the login form or an error page anymore.
    await expect(page.locator('input[name="password"]')).toHaveCount(0);
  });
});

/**
 * Remaining workflow steps. These are test.fixme() until they can be authored against the running
 * stack's live DOM (assessment card selectors, question widgets, submit confirmation, provider-side
 * SMART app). The PHP-side seeding for a battery + assignment is also still TODO
 * (BrowserUatTestCase::seedAssignment). See tests/Uat/Browser/README.md for the full plan.
 */
test.describe('assessment workflow', () => {
  test.fixme('patient opens an assigned assessment', async ({ page }) => {
    // TODO: after login, locate the assigned-assessment card for DC_E2E_ASSIGNMENT_ID and open it.
    void page;
  });

  test.fixme('patient completes and submits the assessment', async ({ page }) => {
    // TODO: answer each item, submit, and wait for the QuestionnaireResponse POST
    //       (page.waitForResponse(/QuestionnaireResponse/)) + a completion confirmation.
    void page;
  });

  test.fixme('provider reviews the submitted result in the management app', async ({ page }) => {
    // TODO: log out, log in as the provider, open the assessment-management SMART app, and assert
    //       the submitted result is visible for the seeded patient.
    void page;
  });
});
