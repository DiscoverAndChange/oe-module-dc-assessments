<?php

/**
 * Browser-UAT seeder: create a patient with ACTIVE portal credentials, an assessment (with a real
 * question so the SPA can render it), and an assignment of that assessment to the patient -- using
 * the module's own services (AssessmentRepository / AssignmentRepository), which need the OpenEMR
 * runtime, so this runs INSIDE the container as the web user (like provision-stack.php). The PHPUnit
 * driver (BrowserUatTestCase) invokes it over `docker exec` and reads the JSON it prints on stdout.
 *
 *   docker exec <c> sh -c "cd <webroot> && su -s /bin/sh apache -c \
 *     'php .../tests/Uat/Browser/tools/seed-assignment.php'"
 *
 * Prints one JSON line: {pid, username, password, clientUuid, assessmentId, assessmentUid,
 * assignmentItemId, assessmentName}. Everything is prefixed 'dcuat' so teardown can find it.
 */

declare(strict_types=1);

$GLOBALS['ignoreAuth'] = true;
$ignoreAuth = true;
$sessionAllowWrite = true;
$_GET['site'] = $_GET['site'] ?? 'default';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';

$dir = __DIR__;
$globals = null;
for ($i = 0; $i < 12; $i++) {
    if (is_file($dir . '/interface/globals.php')) {
        $globals = $dir . '/interface/globals.php';
        break;
    }
    $dir = dirname($dir);
}
if ($globals === null) {
    fwrite(STDERR, "SEED_FAIL could not locate interface/globals.php\n");
    exit(1);
}
require_once $globals;

$modRoot = $GLOBALS['fileroot'] . '/interface/modules/custom_modules/oe-module-dc-assessments';
if (is_file($modRoot . '/vendor/autoload.php')) {
    require_once $modRoot . '/vendor/autoload.php';
}

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssignmentRepository;

try {
    $suffix = bin2hex(random_bytes(4));
    $username = 'dcuat_' . $suffix;
    $password = 'DcUat-' . $suffix . '-Aa1!';
    $assessmentName = 'DcUat Assessment ' . $suffix;
    $assessmentUid = 'dcuat-' . $suffix;

    // 1. patient + active portal credentials (verified: portal_pwd_status=1, no onetime)
    QueryUtils::sqlStatementThrowException(
        "INSERT INTO patient_data (fname, lname, date, pubpid, allow_patient_portal) VALUES ('DcUat', ?, NOW(), ?, 'YES')",
        ['dcuat-' . $suffix, 'dcuat-' . $suffix]
    );
    $pid = (int) QueryUtils::getLastInsertId();
    QueryUtils::sqlStatementThrowException("UPDATE patient_data SET pid = ? WHERE id = ?", [$pid, $pid]);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    QueryUtils::sqlStatementThrowException(
        "INSERT INTO patient_access_onsite (pid, portal_username, portal_login_username, portal_pwd, portal_pwd_status, portal_onetime, date_created) "
        . "VALUES (?, ?, ?, ?, 1, NULL, NOW())",
        [$pid, $username, $username, $hash]
    );

    UuidRegistry::createMissingUuidsForTables(['patient_data']);
    /** @var string $uuidBytes */
    $uuidBytes = QueryUtils::fetchSingleValue("SELECT uuid FROM patient_data WHERE pid = ?", 'uuid', [$pid]);
    $clientUuid = UuidRegistry::uuidToString($uuidBytes);

    // 2. an assessment with a real, answerable question (reuse the module's committed fixture blob)
    $fixture = $modRoot . '/tests/data/Unit/Services/import-assessmentblob.json';
    $blob = [];
    if (is_file($fixture)) {
        /** @var array{AssessmentBlob?: array<int, array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents($fixture), true) ?: [];
        foreach (($data['AssessmentBlob'] ?? []) as $candidate) {
            if (!empty($candidate['_question_sets'])) {
                $blob = $candidate;
                break;
            }
        }
    }
    // stamp it with our unique identity
    $blob['_uid'] = $assessmentUid;
    $blob['_name'] = $assessmentName;
    $blob['_enabled'] = 1;

    $assessmentId = (int) (new AssessmentRepository(new SystemLogger()))
        ->createAssessment($assessmentUid, $assessmentName, 'DcUat seeded assessment', $blob, null);

    // 3. assign it to the patient
    $dateAssigned = new \DateTime();
    $item = new AssignedAssessment();
    $item->setDateAssigned($dateAssigned);
    $item->setName($assessmentName);
    $item->setUid($assessmentUid);
    $item->setAssessmentId($assessmentId);
    $assignment = new Assignment();
    $assignment->setDateAssigned($dateAssigned);
    $assignment->setName('DcUat Assignment ' . $suffix);
    $assignment->setType('Assessment');
    $assignment->setClientId($clientUuid);
    $assignment->addItem($item);
    $saved = (new AssignmentRepository())->saveAssignmentForClient($clientUuid, $assignment, 1);
    $assignmentItemId = $saved->getItems()[0]->getId();

    echo json_encode([
        'pid' => $pid,
        'username' => $username,
        'password' => $password,
        'clientUuid' => $clientUuid,
        'assessmentId' => $assessmentId,
        'assessmentUid' => $assessmentUid,
        'assessmentName' => $assessmentName,
        'assignmentItemId' => $assignmentItemId,
    ]) . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "SEED_FAIL " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
