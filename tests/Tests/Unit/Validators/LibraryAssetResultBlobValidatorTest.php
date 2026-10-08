<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\LibraryAssetResultBlobValidator;
use OpenEMR\Validators\BaseValidator;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for LibraryAssetResultBlobValidator.
 *
 * DATABASE_INSERT_CONTEXT ("db-insert") requires asset.id (numeric), an answers array
 * whose every element carries a v4 UUID id, and v4 UUID assignmentItemId / clientId.
 *
 * DATABASE_UPDATE_CONTEXT ("db-update") copies the insert rules with required(false)
 * applied to each, so the insert fields become optional (but still validated when
 * present) while an additional required v4 UUID id is introduced.
 *
 * validate() is pure Particle; no database or OpenEMR runtime is touched.
 */
class LibraryAssetResultBlobValidatorTest extends TestCase
{
    private const V4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    private const V4_B = '550e8400-e29b-41d4-a716-446655440000';

    private function validInsert(): array
    {
        return [
            'asset' => ['id' => 9],
            'answers' => [
                ['id' => self::V4, 'value' => 'anything goes here'],
            ],
            'assignmentItemId' => self::V4,
            'clientId' => self::V4_B,
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

    // --- DATABASE_INSERT_CONTEXT -----------------------------------------------

    public function testInsertValidPasses(): void
    {
        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($this->validInsert(), BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
        $this->assertSame([], $result->getValidationMessages());
    }

    public function testInsertEmptyAnswersArrayIsAllowed(): void
    {
        $blob = $this->validInsert();
        $blob['answers'] = [];

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
    }

    public function testInsertMissingAssetIdIsRejected(): void
    {
        $blob = $this->validInsert();
        unset($blob['asset']['id']);

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'asset.id');
    }

    public function testInsertNonNumericAssetIdIsRejected(): void
    {
        $blob = $this->validInsert();
        $blob['asset']['id'] = 'nine';

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'asset.id');
    }

    public function testInsertAnswerWithInvalidUuidIsRejected(): void
    {
        $blob = $this->validInsert();
        $blob['answers'] = [
            ['id' => 'not-a-uuid', 'value' => 'x'],
        ];

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        // each() surfaces failures under "answers.<index>.<field>"
        $this->assertHasError($result, 'answers');
    }

    public function testInsertMissingAssignmentItemIdIsRejected(): void
    {
        $blob = $this->validInsert();
        unset($blob['assignmentItemId']);

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'assignmentItemId');
    }

    public function testInsertNonV4ClientIdIsRejected(): void
    {
        $blob = $this->validInsert();
        $blob['clientId'] = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'; // valid format, not v4

        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate($blob, BaseValidator::DATABASE_INSERT_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'clientId');
    }

    // --- DATABASE_UPDATE_CONTEXT -----------------------------------------------

    public function testUpdateWithOnlyIdPasses(): void
    {
        // insert fields are made optional on update; id is the one added requirement
        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate(['id' => self::V4], BaseValidator::DATABASE_UPDATE_CONTEXT);

        $this->assertTrue($result->isValid(), var_export($result->getValidationMessages(), true));
    }

    public function testUpdateMissingIdIsRejected(): void
    {
        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate(['asset' => ['id' => 9]], BaseValidator::DATABASE_UPDATE_CONTEXT);

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
    }

    public function testUpdateNonV4IdIsRejected(): void
    {
        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate(
            ['id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'],
            BaseValidator::DATABASE_UPDATE_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'id');
    }

    public function testUpdateStillValidatesOptionalFieldWhenPresent(): void
    {
        // clientId is optional on update, but an invalid value present is still rejected
        $validator = new LibraryAssetResultBlobValidator();
        $result = $validator->validate(
            ['id' => self::V4, 'clientId' => 'not-a-uuid'],
            BaseValidator::DATABASE_UPDATE_CONTEXT
        );

        $this->assertFalse($result->isValid());
        $this->assertHasError($result, 'clientId');
    }
}
