import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for the DC Assessments browser UAT tier.
 *
 * This project is driven by PHPUnit (tests/Uat/Browser/BrowserUatTestCase::runPlaywright), which
 * sets the env below and reads the JSON report back. It can also be run standalone for authoring
 * (`npx playwright test --headed`) once DC_UAT_BASE_URL + DC_E2E_PATIENT_* are exported.
 *
 *   DC_UAT_BASE_URL      base URL of the running OpenEMR stack (e.g. https://localhost:9300)
 *   DC_E2E_REPORT        absolute path the JSON reporter writes to (set by PHPUnit)
 *   DC_E2E_PATIENT_USER  seeded portal username
 *   DC_E2E_PATIENT_PASS  seeded portal password
 */

const baseURL = process.env.DC_UAT_BASE_URL || 'https://localhost:9300';
const jsonOutputFile = process.env.DC_E2E_REPORT || 'playwright-report/results.json';

export default defineConfig({
  testDir: './specs',
  // the SMART login + FHIR round-trips are not fast; give them room
  timeout: 60_000,
  expect: { timeout: 15_000 },
  // UAT runs serially against one shared seeded stack
  fullyParallel: false,
  workers: 1,
  retries: 0,
  forbidOnly: true,
  reporter: [
    ['json', { outputFile: jsonOutputFile }],
    ['list'],
  ],
  use: {
    baseURL,
    // the dev stack serves a self-signed cert on :9300
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
