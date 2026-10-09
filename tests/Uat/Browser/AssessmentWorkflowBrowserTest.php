<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Uat\Browser;

use PHPUnit\Framework\Attributes\Group;

/**
 * Browser (Playwright) UAT for the patient SMART-app workflow.
 *
 * PHPUnit seeds the deterministic preconditions over the DB, hands the credentials to Playwright via
 * env, runs the browser specs, and asserts on the parsed JSON report. See tests/Uat/Browser/README.md
 * and tests/Uat/Browser/playwright/specs/assessment-workflow.spec.ts.
 *
 * Opt-in: DC_BROWSER_UAT=1 (otherwise self-skips). Run via `composer uat:browser`.
 */
#[Group('browser')]
class AssessmentWorkflowBrowserTest extends BrowserUatTestCase
{
    /**
     * Step 1 of the workflow: a patient with freshly-created (verified) portal credentials can log
     * in through the SMART/OAuth2 flow and reach the assessments SPA dashboard. This is the exact
     * path that used to dead-end with "credentials invalid" before the portal_force_credential_reset
     * fix, so it is the highest-value browser check.
     */
    public function testPatientCanLogInAndReachDashboard(): void
    {
        $patient = self::seedPatientWithPortalCredentials();

        $result = self::runPlaywright(
            [
                'DC_E2E_PATIENT_USER' => $patient['username'],
                'DC_E2E_PATIENT_PASS' => $patient['password'],
                'DC_E2E_PATIENT_PID'  => (string) $patient['pid'],
            ],
            'patient login'
        );

        $this->assertPlaywrightPassed($result);
    }

    /**
     * The full 13-step scenario (enable module -> inject battery -> assign -> create patient +
     * credentials -> patient login -> take assessment(s) -> submit -> provider review). The browser
     * steps beyond login are authored as Playwright test.fixme() until they can be filled in against
     * a running stack's live DOM; the matching PHP-side seeding (battery injection + assignment) is
     * still TODO -- see seedPatientWithPortalCredentials() for the credential half that is done.
     *
     * Kept incomplete (not skipped-by-gating) so it shows up as a visible TODO in UAT runs.
     */
    public function testFullAssessmentWorkflow(): void
    {
        $this->markTestIncomplete(
            'Full enable->assign->take->submit->review workflow pending: assignment/battery seeding '
            . '(BrowserUatTestCase::seedAssignment, TODO) + the test.fixme() browser steps in '
            . 'assessment-workflow.spec.ts need the running stack to author real selectors.'
        );
    }
}
