<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Auth\AuthUtils;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Services\PatientService;
use PHPUnit\Framework\TestCase;

/**
 * DIAGNOSTIC for the SMART-app "credentials invalid" on a patient's FIRST login.
 *
 * The frontend SPA (…/public/frontend/std/assessments/dashboard) authenticates a patient via
 * OpenEMR's OAuth2 "portal-api" flow. That flow is OpenEMR core (AuthUtils::confirmPassword in
 * 'portal-api' mode); this module only overrides the login *template*. Core rejects a patient
 * BEFORE it ever checks the password when the portal account is still "unverified":
 *   - patient_access_onsite.portal_onetime is set, or
 *   - patient_access_onsite.portal_pwd_status != 1
 * A freshly-created patient sits in exactly that state until they complete the standard portal
 * first-login (portal/index.php), which the SMART path has no UI for -> "credentials invalid".
 *
 * These tests pin that behaviour with the SAME (correct) password in both states, so the only
 * variable is the verified flag -- they document the exact precondition the SMART app depends on.
 *
 * THE FIX (table.sql): the module forces the global portal_force_credential_reset='1' (Disable)
 * on install/upgrade. OpenEMR's create_portallogin.php then computes forced_reset_disable=1, and
 * PatientAccessOnsiteService::saveCredentials() stores portal_pwd_status=1 (and no onetime) for
 * every newly-created patient -- i.e. the "verified" state below -- so the first SMART login works
 * without the standard portal first-login/reset step the SMART flow has no UI for.
 */
class PatientPortalLoginPreconditionTest extends TestCase
{
    private int $fxPid = 0;
    private string $username = '';
    private const PASSWORD = 'phptest-Secret-123!';

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = bin2hex(random_bytes(4));
        $this->username = 'phptest_portal_' . $suffix;

        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME
            . " (fname, lname, date, pubpid, allow_patient_portal) VALUES ('phptest', 'phptest-portal', NOW(), ?, 'YES')",
            ['phptest-portal-' . $suffix]
        );
        $this->fxPid = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . PatientService::TABLE_NAME . " WHERE lname = 'phptest-portal' ORDER BY id DESC LIMIT 1",
            'id',
            []
        );
        // portal code keys client rows on pid == id
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . PatientService::TABLE_NAME . " SET pid = ? WHERE id = ?",
            [$this->fxPid, $this->fxPid]
        );

        // a real, verifiable portal password hash (core verifies non-$6$ hashes with password_verify)
        $hash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);
        QueryUtils::sqlStatementThrowException(
            "INSERT INTO patient_access_onsite (pid, portal_username, portal_login_username, portal_pwd, portal_pwd_status, portal_onetime, date_created)"
            . " VALUES (?, ?, ?, ?, 0, 'phptest-onetime-token', NOW())",
            [$this->fxPid, $this->username, $this->username, $hash]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException("DELETE FROM patient_access_onsite WHERE pid = ?", [$this->fxPid]);
        QueryUtils::sqlStatementThrowException("DELETE FROM " . PatientService::TABLE_NAME . " WHERE lname = 'phptest-portal'");
    }

    /** Freshly-created (unverified) patient is rejected even with the CORRECT password -> this is the bug. */
    public function testUnverifiedPatientIsRejectedDespiteCorrectPassword(): void
    {
        $password = self::PASSWORD;
        $result = (new AuthUtils('portal-api'))->confirmPassword($this->username, $password);
        $this->assertFalse(
            $result,
            'an unverified portal account (portal_onetime set / portal_pwd_status != 1) must be rejected '
            . 'by the portal-api flow even with the correct password -- this is the SMART first-login failure'
        );
    }

    /** Once marked verified (the state a bypass would set), the SAME credentials authenticate. */
    public function testVerifiedPatientCanAuthenticate(): void
    {
        QueryUtils::sqlStatementThrowException(
            "UPDATE patient_access_onsite SET portal_pwd_status = 1, portal_onetime = NULL WHERE pid = ?",
            [$this->fxPid]
        );
        $password = self::PASSWORD;
        $result = (new AuthUtils('portal-api'))->confirmPassword($this->username, $password);
        $this->assertTrue(
            $result,
            'a verified portal account (portal_pwd_status=1, portal_onetime=null) with the correct password '
            . 'must authenticate -- this is the state a credential-reset bypass needs to produce at creation'
        );
    }

    /** Sanity: verified account still rejects a wrong password (we did not disable auth, only the verify gate). */
    public function testVerifiedPatientRejectsWrongPassword(): void
    {
        QueryUtils::sqlStatementThrowException(
            "UPDATE patient_access_onsite SET portal_pwd_status = 1, portal_onetime = NULL WHERE pid = ?",
            [$this->fxPid]
        );
        $wrong = 'definitely-not-the-password';
        $result = (new AuthUtils('portal-api'))->confirmPassword($this->username, $wrong);
        $this->assertFalse($result, 'a wrong password must still be rejected');
    }
}
