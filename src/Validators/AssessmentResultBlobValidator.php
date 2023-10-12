<?php

/**
 * Note documentation for this Validator can be found here: https://validator.particle-php.com/en/latest/
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Exception\InvalidValueException;
use Particle\Validator\Rule\Uuid;
use Particle\Validator\Validator;

class AssessmentResultBlobValidator extends BaseValidator
{
    protected function configureValidator()
    {
        parent::configureValidator();

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context) {
                $context->required('id')->uuid(Uuid::UUID_V4);
                $context->required('client_id')->uuid(Uuid::UUID_V4);
                $context->required('data._assignmentItemId')->uuid(Uuid::UUID_V4);
                $context->required('data._assessment._version')->numeric();
                $context->required('data._assessment._uid')->lengthBetween(1, 32);
                $context->required('data._answers')->isArray()->allowEmpty(true)->each(function (Validator $context) {
                    // if we need to do any validation on the answers we can do that here
                    // the uuid's for question_id is not a valid uuid4... not even sure what format it was before...
                    // so we just go off UUID_VALID
                    $context->required('_question_id')->uuid(Uuid::UUID_VALID);
                    // if its a non-scorable question we allow score to be empty
                    $context->required('_score')->numeric()->allowEmpty(true);
                    $context->required('_answer')->allowEmpty(true);
                });
                $context->required('data._flaggedQuestions')->isArray()->allowEmpty(true);
                $context->required('data._scaleResults')->isArray()->allowEmpty(true)
                    ->each(function (Validator $context) {
                    // if we need to do any validation on the scale results we can do that here
                        // the uuid's for scaleId,rangeId is not a valid uuid4... not even sure what format it was before...
                        // so we just go off UUID_VALID
                        $context->required('_scaleId')->uuid(Uuid::UUID_VALID);
                        $context->required('_score')->numeric();
                        $context->required('_rangeId')->uuid(Uuid::UUID_VALID);
                    });
            }
        );
    }
}
