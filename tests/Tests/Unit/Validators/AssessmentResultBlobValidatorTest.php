<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentResultBlobValidator;
use OpenEMR\Validators\BaseValidator;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentResultBlobValidator.
 *
 * Only the DATABASE_INSERT_CONTEXT ("db-insert") is configured. The rules validate a
 * nested result-blob structure: top-level v4 UUIDs (id, client_id), a data._assessment
 * sub-object, and the repeating data._answers / data._scaleResults arrays (validated
 * element-by-element via Particle's each()). Nested/each failures surface under dotted,
 * index-bearing keys such as "data._answers.0._question_id". validate() is pure Particle;
 * no database or OpenEMR runtime is touched.
 */
class AssessmentResultBlobValidatorTest extends TestCase
{
    // version 4 UUID (3rd group starts with 4, 4th with [89ab])
    private const V4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    private const V4_B = '550e8400-e29b-41d4-a716-446655440000';
    // valid-format UUID that is intentionally not a v4 (question/scale/range ids)
    private const VALID_FORMAT = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    private function validBlob(): array
    {
        return [
            'id' => self::V4,
            'client_id' => self::V4_B,
            'data' => [
                '_assignmentItemId' => self::V4,
                '_assessment' => [
                    '_version' => 2,
                    '_uid' => 'assess-uid-01',
                ],
                '_answers' => [
                    ['_question_id' => self::VALID_FORMAT, '_score' => 3, '_answer' => 'Agree'],
                ],
                '_flaggedQuestions' => [],
                '_scaleResults' => [
                    ['_scaleId' => self::VALID_FORMAT, '_score' => 12, '_rangeId' => self::VALID_FORMAT],
                ],
            ],
        ];
    }

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

    public function testFullyPopulatedBlobPasses(): void
    {
        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($this->validBlob(), BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
        $this->assertSame([], $result->getValidationMessages());
    }

    public function testEmptyAnswersFlaggedAndScaleArraysAreAllowed(): void
    {
        $blob = $this->validBlob();
        $blob['data']['_answers'] = [];
        $blob['data']['_scaleResults'] = [];

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
    }

    public function testNonScorableAnswerWithEmptyScoreAndAnswerIsAllowed(): void
    {
        $blob = $this->validBlob();
        $blob['data']['_answers'] = [
            ['_question_id' => self::VALID_FORMAT, '_score' => '', '_answer' => ''],
        ];

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
    }

    public function testMissingIdIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['id']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
    }

    public function testNonV4IdIsRejected(): void
    {
        $blob = $this->validBlob();
        // a valid-format UUID that is not v4 must fail the UUID_V4 rule
        $blob['id'] = self::VALID_FORMAT;

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
    }

    public function testMissingClientIdIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['client_id']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'client_id');
    }

    public function testMissingAssignmentItemIdIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['data']['_assignmentItemId']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._assignmentItemId');
    }

    public function testNonNumericAssessmentVersionIsRejected(): void
    {
        $blob = $this->validBlob();
        $blob['data']['_assessment']['_version'] = 'v-two';

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._assessment._version');
    }

    public function testMissingAssessmentUidIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['data']['_assessment']['_uid']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._assessment._uid');
    }

    public function testMissingAnswersArrayIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['data']['_answers']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._answers');
    }

    public function testAnswerWithInvalidQuestionIdIsRejected(): void
    {
        $blob = $this->validBlob();
        $blob['data']['_answers'] = [
            ['_question_id' => 'not-a-uuid', '_score' => 1, '_answer' => 'x'],
        ];

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        // each() surfaces failures under "data._answers.<index>.<field>"
        $this->assertHasError($result, 'data._answers');
    }

    public function testMissingFlaggedQuestionsArrayIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['data']['_flaggedQuestions']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._flaggedQuestions');
    }

    public function testMissingScaleResultsArrayIsRejected(): void
    {
        $blob = $this->validBlob();
        unset($blob['data']['_scaleResults']);

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._scaleResults');
    }

    public function testScaleResultWithNonNumericScoreIsRejected(): void
    {
        $blob = $this->validBlob();
        $blob['data']['_scaleResults'] = [
            ['_scaleId' => self::VALID_FORMAT, '_score' => 'high', '_rangeId' => self::VALID_FORMAT],
        ];

        $validator = new AssessmentResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'data._scaleResults');
    }
}
