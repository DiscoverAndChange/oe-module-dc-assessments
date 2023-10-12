<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Validator;

class AssessmentGroupValidator extends BaseValidator
{
    const DATABASE_ADD_ASSESSMENT_CONTEXT = "db-insert-assessment";
    const DATABASE_UPDATE_ASSESSMENT_CONTEXT = "db-update-assessment";

    protected function configureValidator()
    {
        parent::configureValidator();

        array_push(
            $this->supportedContexts,
            self::DATABASE_ADD_ASSESSMENT_CONTEXT,
            self::DATABASE_UPDATE_ASSESSMENT_CONTEXT
        );

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context) {
                $context->required('name')->lengthBetween(1, 100);
                $context->optional('appointmentId')->uuid();
            }
        );

        $this->validator->context(
            self::DATABASE_ADD_ASSESSMENT_CONTEXT,
            function (Validator $context) {
                $context->required("uid")->lengthBetween(1, 32);
                $context->required("groupId")->numeric();
                $context->optional('appointmentId')->uuid();
            }
        );

        $this->validator->context(
            self::DATABASE_UPDATE_ASSESSMENT_CONTEXT,
            function (Validator $context) {
                $context->required("groupId")->numeric();
            }
        );
    }
}
