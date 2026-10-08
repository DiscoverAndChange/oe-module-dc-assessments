<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentGroupValidator;
use OpenEMR\Validators\BaseValidator;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentGroupValidator.
 *
 * The validator extends OpenEMR's BaseValidator and only configures Particle rules
 * in its constructor; validate() runs those rules and returns a ProcessingResult
 * whose isValid() is true only when there are no validation messages. No database
 * or OpenEMR runtime is touched by the code paths exercised here.
 *
 * Contexts characterized:
 *  - DATABASE_INSERT_CONTEXT          ("db-insert"):            name (1..100), optional appointmentId uuid
 *  - DATABASE_ADD_ASSESSMENT_CONTEXT  ("db-insert-assessment"): uid (1..32), groupId numeric, optional appointmentId uuid
 *  - DATABASE_UPDATE_ASSESSMENT_CONTEXT ("db-update-assessment"): groupId numeric
 */
class AssessmentGroupValidatorTest extends TestCase
{
    private const VALID_UUID = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

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

    // --- DATABASE_INSERT_CONTEXT ------------------------------------------------

    public function testInsertValidNameOnlyPasses(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(['name' => 'Depression battery'], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getValidationMessages());
    }

    public function testInsertValidWithOptionalAppointmentUuidPasses(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['name' => 'Depression battery', 'appointmentId' => self::VALID_UUID],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertTrue($result->isValid());
    }

    public function testInsertMissingNameIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate([], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertEmptyNameIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(['name' => ''], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertNameExceedingMaxLengthIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(['name' => str_repeat('a', 101)], BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'name');
    }

    public function testInsertInvalidAppointmentUuidIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['name' => 'ok', 'appointmentId' => 'not-a-uuid'],
            BaseValidator::DATABASE_INSERT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'appointmentId');
    }

    // --- DATABASE_ADD_ASSESSMENT_CONTEXT ---------------------------------------

    public function testAddAssessmentValidPasses(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['uid' => 'abc123', 'groupId' => 7],
            AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT
        );

        $this->assertTrue($result->isValid());
    }

    public function testAddAssessmentMissingUidIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['groupId' => 7],
            AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'uid');
    }

    public function testAddAssessmentUidExceedingMaxLengthIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['uid' => str_repeat('u', 33), 'groupId' => 7],
            AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'uid');
    }

    public function testAddAssessmentMissingGroupIdIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['uid' => 'abc123'],
            AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'groupId');
    }

    public function testAddAssessmentNonNumericGroupIdIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['uid' => 'abc123', 'groupId' => 'not-numeric'],
            AssessmentGroupValidator::DATABASE_ADD_ASSESSMENT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'groupId');
    }

    // --- DATABASE_UPDATE_ASSESSMENT_CONTEXT ------------------------------------

    public function testUpdateAssessmentValidGroupIdPasses(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            ['groupId' => 42],
            AssessmentGroupValidator::DATABASE_UPDATE_ASSESSMENT_CONTEXT
        );

        $this->assertTrue($result->isValid());
    }

    public function testUpdateAssessmentMissingGroupIdIsRejected(): void
    {
        $validator = new AssessmentGroupValidator();
        $result = $validator->validate(
            [],
            AssessmentGroupValidator::DATABASE_UPDATE_ASSESSMENT_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'groupId');
    }

    // --- context handling -------------------------------------------------------

    public function testUnsupportedContextThrows(): void
    {
        $validator = new AssessmentGroupValidator();

        $this->expectException(\RuntimeException::class);
        $validator->validate(['name' => 'x'], 'no-such-context');
    }
}
