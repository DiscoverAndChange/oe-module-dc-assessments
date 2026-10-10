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
     * Assignment workflow: seed a patient + an assessment (with a real question) + an assignment via
     * the module's own services (container seeder), then drive the SPA: the dashboard lists the
     * assigned assessment, and the patient opens it, answers, and submits (QuestionnaireResponse POST
     * to the portal route -> 201 -> completion confirmation).
     */
    public function testPatientWorkflow(): void
    {
        $seed = self::seedAssignedAssessment();

        $result = self::runPlaywright(
            [
                'DC_E2E_PATIENT_USER'   => (string) $seed['username'],
                'DC_E2E_PATIENT_PASS'   => (string) $seed['password'],
                'DC_E2E_PATIENT_PID'    => (string) $seed['pid'],
                'DC_E2E_ASSESSMENT_NAME' => (string) $seed['assessmentName'],
            ],
            'assessment workflow'
        );

        $this->assertPlaywrightPassed($result);
    }

    /**
     * Provider review: seed + have the patient complete the assessment, then the provider logs into the
     * admin app (confidential provider client, server-side token broker) and sees the patient's
     * completed assessment with a report to view -- the final step of the 13-step scenario.
     */
    public function testProviderReviewsResult(): void
    {
        $seed = self::seedAssignedAssessment();

        $result = self::runPlaywright(
            [
                'DC_E2E_PATIENT_USER'     => (string) $seed['username'],
                'DC_E2E_PATIENT_PASS'     => (string) $seed['password'],
                'DC_E2E_PATIENT_LASTNAME' => (string) $seed['lastName'],
                'DC_E2E_ASSESSMENT_NAME'  => (string) $seed['assessmentName'],
            ],
            'provider review'
        );

        $this->assertPlaywrightPassed($result);
    }

    /**
     * Provider EHR launch: a provider already authenticated in OpenEMR launches the admin app in-EHR
     * (ehr-launch-client.php). The SMART launch token must carry through the authorize so the app reuses
     * the EHR session WITHOUT a second login -- this guards the regression where the SPA dropped the
     * launch param and forced a re-login.
     */
    public function testProviderEhrLaunchReview(): void
    {
        $seed = self::seedAssignedAssessment();
        if (($seed['providerClientId'] ?? '') === '') {
            $this->markTestSkipped('provider client not registered yet; run tools/provision-stack.php.');
        }

        $result = self::runPlaywright(
            [
                'DC_E2E_PATIENT_USER'       => (string) $seed['username'],
                'DC_E2E_PATIENT_PASS'       => (string) $seed['password'],
                'DC_E2E_PATIENT_LASTNAME'   => (string) $seed['lastName'],
                'DC_E2E_ASSESSMENT_NAME'    => (string) $seed['assessmentName'],
                'DC_E2E_PROVIDER_CLIENT_ID' => (string) $seed['providerClientId'],
            ],
            'provider ehr launch'
        );

        $this->assertPlaywrightPassed($result);
    }
}
