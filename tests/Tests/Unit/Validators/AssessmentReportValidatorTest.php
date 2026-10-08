<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentReportValidator;
use OpenEMR\Validators\BaseValidator;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentReportValidator.
 *
 * validate() runs Particle rules and returns a ProcessingResult (isValid() is true
 * only when there are no messages). No database or OpenEMR runtime is exercised.
 *
 * Contexts characterized:
 *  - DATABASE_INSERT_CONTEXT ("db-insert"): required id (1..255), required name (1..255),
 *    optional linkedGroup.id numeric, optional assessment_uid (1..32), optional hostSites array.
 *  - DATABASE_UPDATE_CONTEXT ("db-update"): copyContext of the insert rules, so it enforces
 *    the same required id/name.
 */
class AssessmentReportValidatorTest extends TestCase
{
    private function assertHasError(ProcessingResult $result, string $field): void
    {
        $messages = $result->getValidationMessages();
        $found = array_key_exists($field, $messages);
        if (!$found) {
            foreach (array_keys($messages) as $key) {
                if (strpos((string)$key, $field) === 0) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue(
            $found,
            "expected a validation message keyed by '$field', got: [" . implode(', ', array_keys($messages)) . ']'
        );
    }

    public function testInsertMinimalValidPasses(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => 'Intake report'],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getValidationMessages());
    }

    public function testInsertFullValidWithOptionalFieldsPasses(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            [
                'id' => 'report-1',
                'name' => 'Intake report',
                'linkedGroup' => ['id' => 12],
                'assessment_uid' => 'uid-0123456789',
                'hostSites' => ['site-a', 'site-b'],
            ],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertTrue($result->isValid());
    }

    public function testInsertMissingIdIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(['name' => 'Intake report'], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
    }

    public function testInsertMissingNameIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(['id' => 'report-1'], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertEmptyNameIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => ''],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertNameExceedingMaxLengthIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => str_repeat('n', 256)],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertNonNumericLinkedGroupIdIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => 'ok', 'linkedGroup' => ['id' => 'abc']],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'linkedGroup.id');
    }

    public function testInsertAssessmentUidExceedingMaxLengthIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => 'ok', 'assessment_uid' => str_repeat('u', 33)],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'assessment_uid');
    }

    public function testInsertNonArrayHostSitesIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => 'ok', 'hostSites' => 'not-an-array'],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'hostSites');
    }

    public function testUpdateContextSharesInsertRulesAndValidPasses(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate(
            ['id' => 'report-1', 'name' => 'Intake report'],
            BaseValidator::DATABASE_UPDATE_CONTEXT
        );

        $this->assertTrue($result->isValid());
    }

    public function testUpdateContextMissingRequiredFieldsIsRejected(): void
    {
        $validator = new AssessmentReportValidator();
        $result = $validator->validate([], BaseValidator::DATABASE_UPDATE_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
        $this->assertHasError($result, 'name');
    }
}
