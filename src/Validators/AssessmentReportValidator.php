<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Validator;

class AssessmentReportValidator extends BaseValidator
{
    protected function configureValidator()
    {
        parent::configureValidator();

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context) {
                $context->required('id')->lengthBetween(1, 255);
                $context->required('name')->lengthBetween(1, 255);
                $context->optional('linkedGroup.id')->numeric();
                $context->optional('assessment_uid')->lengthBetween(1, 32);
                $context->optional('hostSites')->isArray();
            }
        );

        $this->validator->context(
            self::DATABASE_UPDATE_CONTEXT,
            function (Validator $context) {
                $context->copyContext(self::DATABASE_INSERT_CONTEXT);
            }
        );
    }
}
