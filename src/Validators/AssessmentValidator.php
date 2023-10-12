<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Rule\Uuid;
use Particle\Validator\Validator;

class AssessmentValidator extends BaseValidator
{
    protected function configureValidator()
    {
        parent::configureValidator();

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context) {
                $context->required('_uid')->lengthBetween(1, 32);
                $context->required('_name')->lengthBetween(1, 255);
                $context->required('_description')->lengthBetween(1, 65535);
            //                $context->required('status')->inArray(['published','archived']);
            }
        );

        $this->validator->context(
            self::DATABASE_UPDATE_CONTEXT,
            function (Validator $context) {
                $context->copyContext(
                    self::DATABASE_INSERT_CONTEXT,
                    function ($rules) {
                       // any update to the rules would go here
                    }
                );
            }
        );
    }
}
